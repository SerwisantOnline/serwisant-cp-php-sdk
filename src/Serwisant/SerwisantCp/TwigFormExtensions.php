<?php

namespace Serwisant\SerwisantCp;

use Adbar;
use Twig\Environment;
use Twig\Error\Error;
use Twig\TwigFunction;
use Symfony\Component\HttpFoundation\ParameterBag;
use Serwisant\SerwisantApi\Types\SchemaPublic;

class TwigFormExtensions extends TwigExtensions
{
  /**
   * @return Environment
   */
  public function call()
  {
    $this->twig->addFunction(new TwigFunction('form_field', function (array $options, ParameterBag $post_data, $errors) {
      $html = '';
      $html .= "<div class='form-floating'>\n";
      $html .= $this->formField($options, $post_data, $errors) . "\n";
      $html .= "</div>\n";
      return new \Twig\Markup($html, 'UTF-8');
    }));

    $this->twig->addFunction(new TwigFunction('custom_form_field', function ($field, $argument, ParameterBag $post_data, $errors) {
      $html = '';
      $html .= "<div class='form-floating'>\n";
      $html .= $this->customFormField($field, $argument, $post_data, $errors) . "\n";
      $html .= "</div>\n";
      return new \Twig\Markup($html, 'UTF-8');
    }));

    return $this->twig;
  }

  private function formField(array $options, ParameterBag $post_data, $errors)
  {
    $options = new Adbar\Dot($options);
    $post_data = new Adbar\Dot($post_data->all());

    $argument = $options->get('argument', '');
    if ($argument === '') {
      throw new Error('missing argument key in options');
    }
    $arguments = explode('.', $argument);
    $name_container = array_shift($arguments);
    $name_arr = join('', array_map(function ($el) {
      return "[{$el}]";
    }, $arguments));

    $id = $options->get('id', "{$name_container}_" . join('_', $arguments));
    $name = $options->get('name', "{$name_container}{$name_arr}");

    // Priorytet ma to, co zejdzie z POST, później wartość przekazana z konfiguracji
    $value = $post_data->get($argument);
    if ($value === null) {
      $value = $options->get('value');
    }
    $value = twig_escape_filter($this->twig, $value);

    $tr_errors = $options->get('tr_errors');
    $capture_argument_errors = $options->get('capture_argument_errors', []);
    $argument_errors = [];
    foreach ($errors as $error) {
      if ($error->argument === $argument || in_array($error->argument, $capture_argument_errors)) {
        if (is_null($tr_errors)) {
          $argument_errors[] = $this->t(['errors', $error->code]);
        } else {
          $argument_errors[] = $this->t_with_fallback([$tr_errors, $error->code], ['errors', $error->code]);
        }
      }
    }
    if (count($argument_errors) > 0) {
      $error_title = implode(', ', $argument_errors);
      $class_error = ' is-invalid';
    } else {
      $error_title = '';
      $class_error = '';
    }

    $caption = $options->get('caption', false);
    if ($caption) {
      $label = "<label for='{$id}' class='form-label'>{$options->get('caption')}</label>";
    } else {
      $label = '';
    }

    $html = '';
    $type = $options->get('type', '');

    switch ($type) {
      case 'hidden':
        $html .= "<input type='{$options->get('type')}' id='{$id}' name='{$name}' value='{$value}'>";
        break;

      case 'text':
      case 'password':
        $class = "{$options->get('class', 'form-control')}{$class_error}";
        $html .= "<input type='{$options->get('type')}' id='{$id}' class='{$class}' name='{$name}' data-bs-content='{$error_title}' value='{$value}' placeholder='{$caption}' title='{$caption}'";
        if ($type == 'password') {
          $html .= " autocomplete='off' ";
        }
        $html .= ">";
        $html .= $label;
        break;

      case 'datalist':
        $class = "{$options->get('class', 'form-control')}{$class_error}";
        $html .= "<input list='{$id}_datalist' data-url='{$options->get('data_url', '')}'  id='{$id}' class='{$class}' name='{$name}' data-bs-content='{$error_title}' value='{$value}' placeholder='{$caption}' title='{$caption}'>";
        $html .= $label;
        $html .= "<datalist id='{$id}_datalist'>";
        $html .= "</datalist>";
        break;

      case 'datetime':
      case 'date':
        $class = "{$options->get('class', 'form-control')}{$class_error}";
        $html .= "<input type='text' id='{$id}' class='{$class}' name='{$name}' data-bs-content='{$error_title}' value='{$value}' placeholder='{$caption}' title='{$caption}' readonly='readonly' data-field='{$options->get('type', '')}'>";
        $html .= $label;
        break;

      case 'textarea':
        $class = "{$options->get('class', 'form-control')}{$class_error}";
        $html .= "<textarea id='{$id}' class='{$class}' name='{$name}' data-bs-content='{$error_title}' title='{$caption}' rows='100'>{$value}</textarea>";
        $html .= $label;
        break;

      case 'radios':
        $html .= '<fieldset class="row"><legend class="col-form-label col-4 pt-0">';
        $html .= $options->get('caption');
        $html .= '</legend><div class="col-8">';
        foreach ($options->get('options', []) as $v => $t) {
          $checked = ($v == $value) ? "checked='checked'" : "";
          $html .= '<div class="form-check">';
          $html .= "<input class='form-check-input' type='radio' name='{$name}' id='{$id}_{$v}' value='{$v}' {$checked}>";
          $html .= "<label class='form-check-label' for='{$id}_{$v}'>{$t}</label>";
          $html .= '</div>';
        }
        $html .= '</div></fieldset>';
        break;

      case 'select':
        $class = "{$options->get('class', 'form-select')}{$class_error}";
        $html .= "<select id='{$id}' class='{$class}' name='{$name}' data-bs-content='{$error_title}' title='{$caption}'>";
        $rows = $options->get('options', []);
        if ($options->get('include_blank', false)) {
          $rows = ['' => ''] + $rows;
        }
        foreach ($rows as $v => $t) {
          if ($v == $value) {
            $selected = 'selected';
          } else {
            $selected = '';
          }
          $html .= "<option {$selected} value='{$v}'>{$t}</option>";
        }
        $html .= "</select>";
        $html .= $label;
        break;

      case 'checkbox':
      case 'switch':
        if ($type == 'switch') {
          $class = "{$options->get('class', 'form-check form-switch form-switch-md')}";
        } else {
          $class = "{$options->get('class', 'form-check')}";
        }
        if (($post_data->isEmpty() && $options->get('checked', false)) || !$post_data->isEmpty() && $post_data->get($argument, false)) {
          $checked = "checked='checked'";
        } else {
          $checked = "";
        }
        $html .= "<div class=\"{$class}\">\n";
        $html .= "<input type='checkbox' {$checked} id={$id} class='form-check-input{$class_error}' name='{$name}' value='{$value}' data-bs-content='{$error_title}' title='{$caption}'>\n";
        if ($caption) {
          $html .= "<label for='{$id}' class='form-check-label'>{$caption}</label>\n";
        }
        $html .= "</div>\n";
        break;

      default:
        throw new Error("unsupported type {$options->get('type')}");
    }

    return $html;
  }

  private function customFormField(object $field, array $options, ParameterBag $post_data, $errors)
  {
    $html_prepend = '';
    $html_append = '';

    $options = new Adbar\Dot($options);

    $argument = $options->get('argument');
    if (!$argument) {
      throw new Error('Argument keyword is expected in options');
    }

    $value = $options->get('value');

    $options['argument'] = "{$argument}.value";
    $options['caption'] = $field->name;

    switch ($field->type) {
      case SchemaPublic\CustomFieldType::CHECKBOX:
        $options['type'] = 'checkbox';
        $options['checked'] = ($value == '1');
        $options['value'] = 1;
        $form_field = $this->formField($options->all(), $post_data, $errors);
        break;

      case SchemaPublic\CustomFieldType::TEXT:
        $options['type'] = 'text';
        $form_field = $this->formField($options->all(), $post_data, $errors);
        break;

      case SchemaPublic\CustomFieldType::TEXTAREA:
        $options['type'] = 'textarea';
        $form_field = $this->formField($options->all(), $post_data, $errors);
        break;

      case SchemaPublic\CustomFieldType::SELECT:
        $select_options = ['' => ''];
        foreach ($field->selectOptions as $option) {
          $select_options[$option] = $option;
        }
        $options['type'] = 'select';
        $options['options'] = $select_options;
        $form_field = $this->formField($options->all(), $post_data, $errors);
        break;

      case SchemaPublic\CustomFieldType::PASSWORD:
        $options['type'] = 'password';
        $form_field = $this->formField($options->all(), $post_data, $errors);
        break;

      case SchemaPublic\CustomFieldType::DATE:
        $options['type'] = 'date';
        $form_field = $this->formField($options->all(), $post_data, $errors);
        break;

      default:
        return '';
    }

    $html = '';

    $options_hidden_cf = [
      'type' => 'hidden',
      'argument' => "{$argument}.customField",
      'value' => $field->ID
    ];
    $html .= $this->formField($options_hidden_cf, $post_data, $errors) . "\n";

    if ($options->get('pk')) {
      $options_hidden_id = [
        'type' => 'hidden',
        'argument' => "{$argument}.ID",
        'value' => $options->get('pk')
      ];
      $html .= $this->formField($options_hidden_id, $post_data, $errors) . "\n";
    }

    $html .= $html_prepend . "\n";
    $html .= (string)$form_field . "\n";
    $html .= $html_append . "\n";

    return $html;
  }
}