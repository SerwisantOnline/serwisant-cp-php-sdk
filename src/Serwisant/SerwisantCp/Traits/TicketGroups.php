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
   * View variables for group and priority fields of new ticket form.
   *
   * @param SchemaCustomer\Dictionary[]|SchemaPublic\Dictionary[] $groups
   * @param mixed $preselected_group group ID to select when form is displayed first time (eg. from URL), ignored if unknown
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
    // group selected in the form (POST after validation errors) takes precedence over the preselected one
    $selected_group = $this->request->request->all()['ticket']['group'] ?? $preselected_group;
    if (!is_string($selected_group) || !array_key_exists($selected_group, $select_options)) {
      $selected_group = null;
    }
    $selected_group_priority = $selected_group ? ($priorities[$selected_group] ?? null) : null;

    return [
      'ticket_groups_select_options' => $select_options,
      'defaultTicketGroup' => $preselected_group,
      'ticket_groups_priorities' => $priorities,
      'selectedTicketGroupPriority' => $selected_group_priority,
      // when ticket groups are available, priority is chosen by the customer only for a group without assigned priority
      'priorityHidden' => count($select_options) > 0 && (!$selected_group || $selected_group_priority),
    ];
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
