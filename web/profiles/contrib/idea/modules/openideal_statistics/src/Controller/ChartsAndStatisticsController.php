<?php

namespace Drupal\openideal_statistics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\openideal_statistics\Form\OpenidealStatisticsDateSelectForm;

/**
 * Class ChartsAndStatisticsController.
 */
class ChartsAndStatisticsController extends ControllerBase {

  /**
   * Charts.
   */
  public function charts() {
    return $this->formBuilder()->getForm(OpenidealStatisticsDateSelectForm::class);
  }

}
