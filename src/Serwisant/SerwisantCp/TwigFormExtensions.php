<?php

namespace Serwisant\SerwisantCp;

use Adbar;
use Twig\Environment;
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
      $html .= "<div class='form-floating'>";
      $html .= $this->formField($options, $post_data, $errors);
      $html .= "</div>";
      return new \Twig\Markup($html, 'UTF-8');
    }));

    $this->twig->addFunction(new TwigFunction('custom_form_field', function ($field, $argument, ParameterBag $post_data, $errors) {
      $html = '';
      $html .= "<div class='form-floating'>";
      $html .= $this->customFormField($field, $argument, $post_data, $errors);
      $html .= "</div>";
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
      throw new \Twig\Error\Error('missing argument key in options');
    }
    $arguments = explode('.', $argument);
    $name_container = array_shift($arguments);
    $name_arr = join('', array_map(function ($el) {
      return "[{$el}]";
    }, $arguments));

    $id = $options->get('id', uniqid($argument));
    $name = $options->get('name', "{$name_container}{$name_arr}");
    $value = $options->get('value', twig_escape_filter($this->twig, $post_data->get($argument)));

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
      $title = implode(', ', $argument_errors);
      $class_error = ' is-invalid';
    } else {
      $title = '';
      $class_error = '';
    }

    $caption = $options->get('caption', false);
    if ($caption) {
      $label = "<label for='{$id}' class='form-label'>{$options->get('caption')}</label>";
    } else {
      $label = '';
    }

    $html = '';

    switch ($options->get('type', '')) {
      case 'hidden':
        $html .= "<input type='{$options->get('type')}' id='{$id}' name='{$name}' value='{$value}'>";
        break;

      case 'text':
      case 'password':
        $class = "{$options->get('class', 'form-control')}{$class_error}";
        $html .= "<input type='{$options->get('type')}' id='{$id}' class='{$class}' name='{$name}' title='{$title}' value='{$value}' placeholder='{$caption}'>";
        $html .= $label;
        break;

      case 'textarea':
        $class = "{$options->get('class', 'form-control')}{$class_error}";
        $html .= "<textarea id='{$id}' class='{$class}' name='{$name}' title='{$title}' rows='100'>{$value}</textarea>";
        $html .= $label;
        break;

      case 'select':
        $class = "{$options->get('class', 'form-select')}{$class_error}";
        $html .= "<select id='{$id}' class='{$class}' name='{$name}'>";
        foreach ($options->get('options', []) as $v => $t) {
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
        $class = "{$options->get('class', 'form-check-input')}{$class_error}";
        if ($post_data->get($argument, false)) {
          $checked = "checked='checked'";
        } else {
          $checked = "";
        }
        $html .= '<div class="form-check">';
        $html .= "<input type='checkbox' {$checked} id={$id} class='{$class}' name='{$name}' value='{$value}'>";
        if ($caption) {
          $html .= "<label for='{$id}' class='form-check-label'>{$caption}</label>";
        }
        $html .= '</div>';
        break;

      default:
        throw new \Twig\Error\Error("unsupported type {$options->get('type')}");
    }

    return $html;
  }

  private function customFormField($field, $argument, ParameterBag $post_data, $errors)
  {
    $html_prepend = '';
    $html_append = '';

    $options = [
      'argument' => "{$argument}.value",
      'caption' => $field->name,
    ];

    switch ($field->type) {
      case SchemaPublic\CustomFieldType::CHECKBOX:
        $options['type'] = 'checkbox';
        $options['value'] = 1;
        $form_field = $this->formField($options, $post_data, $errors);
        break;

      case SchemaPublic\CustomFieldType::TEXT:
        $options['type'] = 'text';
        $form_field = $this->formField($options, $post_data, $errors);
        break;

      case SchemaPublic\CustomFieldType::TEXTAREA:
        $options['type'] = 'textarea';
        $form_field = $this->formField($options, $post_data, $errors);
        break;

      case SchemaPublic\CustomFieldType::SELECT:
        $select_options = [];
        foreach ($field->selectOptions as $option) {
          $select_options[$option] = $option;
        }
        $options['type'] = 'select';
        $options['options'] = $select_options;
        $form_field = $this->formField($options, $post_data, $errors);
        break;

      case SchemaPublic\CustomFieldType::PASSWORD:
        $options['type'] = 'password';
        $form_field = $this->formField($options, $post_data, $errors);
        break;

      case SchemaPublic\CustomFieldType::DATE:
        $options['type'] = 'text';
        $options['caption'] = null;

        $html_prepend .= "<div class='datepicker date form-floating'>";
        $form_field = $this->formField($options, $post_data, $errors);
        $html_append .= "<div class='input-group-append'</div>";
        $html_append .= "</div>";
        $html_append .= "<label>{$field->name}</label>";
        break;

      default:
        return '';
    }

    $options_hidden = [
      'type' => 'hidden',
      'argument' => "{$argument}.ID",
      'value' => $field->ID
    ];

    $html = '';
    $html .= (string)$this->formField($options_hidden, $post_data, $errors);
    $html .= $html_prepend;
    $html .= (string)$form_field;
    $html .= $html_append;

    return $html;
  }
}