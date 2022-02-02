# Bootstrap 4 Modal

This project allows user to load bootstrap 4 modal
by [Drupal Ajax Dailog Boxes].

## Requirements

 - [Drupal Bootstrap] or any other theme that utilizes the
 Bootstrap framework modal and classes.

## Installation

Install as you would normally install a contributed Drupal module. See:
https://drupal.org/documentation/install/modules-themes/modules-8 for further
information.

## Configuration

No special configuration needed.

## Usage

```twig
<a
  href="[some-links]"
  class="use-ajax"
  data-dialog-type="bootstrap4_modal"
  data-dialog-options="{&quot;dialogClasses&quot;:&quot;modal-dialog-centered&quot;,&quot;dialogShowHeader&quot;:false}"
>
  Open in Bootstrap 4 Modal
</a>
```

## Maintainers

- Mark Quirvien Cristobal ([vhin0210](https://www.drupal.org/u/vhin0210))

[Drupal Ajax Dailog Boxes]:https://www.drupal.org/docs/drupal-apis/ajax-api/ajax-dialog-boxes
[Drupal Bootstrap]:https://www.drupal.org/project/bootstrap
