<?php

namespace Serwisant\SerwisantCp\Traits;

use Serwisant\SerwisantApi\Types\SchemaCustomer;
use Serwisant\SerwisantApi\Types\SchemaPublic;

/**
 * Ticket groups (dictionary entries of TICKET_CATEGORY type) in new ticket forms: group select, custom fields
 * depending on group (CustomField.concern = Dictionary.ID) and priority assigned to a group.
 */
trait TicketGroups
{
  /**
   * View variables for "Open a ticket" button (`tickets/create_button.html.twig`): groups marked with `asMenuItem`
   * are offered in a dropdown; when every group is a menu item, the whole button becomes the dropdown.
   *
   * @param SchemaCustomer\Dictionary[]|SchemaPublic\Dictionary[] $groups
   */
  private function ticketGroupsMenuVariables(array $groups): array
  {
    $menu_groups = array_values(array_filter($groups, fn($group) => $group->asMenuItem === true));
    return [
      'ticketGroupsMenu' => $menu_groups,
      'ticketGroupsMenuOnly' => count($menu_groups) > 0 && count($menu_groups) === count($groups),
    ];
  }

  /**
   * @return SchemaCustomer\Dictionary[]
   */
  private function fetchCustomerTicketGroups(): array
  {
    $filter = new SchemaCustomer\DictionaryEntriesFilter(['type' => SchemaCustomer\DictionaryType::TICKET_CATEGORY]);
    return $this->apiCustomer()->customerQuery()->dictionaryEntries($filter);
  }

  /**
   * View variables for group and priority fields of new ticket form.
   *
   * @param SchemaCustomer\Dictionary[]|SchemaPublic\Dictionary[] $groups
   * @param mixed $preselected_group group ID passed in URL (`group` query parameter, eg. from tickets groups menu) - the
   *   group is locked in the form (`ticketGroupLocked`), ignored if unknown
   */
  private function ticketGroupsFormVariables(array $groups, $preselected_group = null): array
  {
    $select_options = [];
    $priorities = [];
    foreach ($groups as $group) {
      $select_options[$group->ID] = $group->name;
      if ($group->priority) {
        $priorities[$group->ID] = $group->priority->ID;
      }
    }

    if (!is_string($preselected_group) || !array_key_exists($preselected_group, $select_options)) {
      $preselected_group = null;
    }
    // preselected group is locked; otherwise group selected in the form (POST after validation errors)
    $selected_group = $preselected_group ?? ($this->request->request->all()['ticket']['group'] ?? null);
    if (!is_string($selected_group) || !array_key_exists($selected_group, $select_options)) {
      $selected_group = null;
    }
    $selected_group_priority = $selected_group ? ($priorities[$selected_group] ?? null) : null;

    return [
      'ticket_groups_select_options' => $select_options,
      'defaultTicketGroup' => $preselected_group,
      // preselected group can't be changed in the form: rendered as disabled field + hidden input, form is posted with
      // `group` query parameter, which is enforced by lockTicketGroup()
      'ticketGroupLocked' => $preselected_group !== null,
      'lockedTicketGroupName' => $preselected_group !== null ? $select_options[$preselected_group] : null,
      'ticket_groups_priorities' => $priorities,
      'selectedTicketGroupPriority' => $selected_group_priority,
      // when ticket groups are available, priority is chosen by the customer only for a group without assigned priority
      'priorityHidden' => count($select_options) > 0 && (!$selected_group || $selected_group_priority),
    ];
  }

  /**
   * Group locked in the form (`group` query parameter of the form action) always wins over posted one. Validated later
   * by applyTicketGroup().
   */
  private function lockTicketGroup(array $ticket): array
  {
    $locked_group = $this->request->query->all()['group'] ?? null;
    if (is_string($locked_group) && trim($locked_group) !== '') {
      $ticket['group'] = $locked_group;
    }
    return $ticket;
  }

  /**
   * Normalizes `group` of posted ticket. Blank option means ticket without group. Priority assigned to the group always
   * wins over posted one (priority field is hidden in the form in such case) - it can't be manipulated by the customer.
   * Group which is not one of the available groups (or is not a string) is rejected - otherwise priority of such group
   * couldn't be enforced.
   *
   * @param callable $fetch_groups returns SchemaCustomer\Dictionary[]|SchemaPublic\Dictionary[], called only if group is given
   * @return array|null ticket to pass to TicketInput, null if posted group is invalid
   */
  private function applyTicketGroup(array $ticket, callable $fetch_groups): ?array
  {
    if (!array_key_exists('group', $ticket)) {
      return $ticket;
    }
    if (!is_string($ticket['group'])) {
      return null;
    }
    if (trim($ticket['group']) === '') {
      unset($ticket['group']);
      return $ticket;
    }
    foreach ($fetch_groups() as $group) {
      if ($group->ID === $ticket['group']) {
        if ($group->priority) {
          $ticket['priority'] = $group->priority->ID;
        }
        return $ticket;
      }
    }
    return null;
  }
}
