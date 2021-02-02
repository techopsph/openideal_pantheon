(function ($) {

  Drupal.votingApiReaction = {
    className: "votingapi-reaction-form"
  };

  Drupal.behaviors.votingApiReaction = {
    attach: function () {
      // We extend Drupal.ajax objects for all AJAX elements in our form
      for (var instance in Drupal.ajax.instances) {
        if (Drupal.ajax.instances.hasOwnProperty(instance)
            && Drupal.ajax.instances[instance] !== null
            && Drupal.ajax.instances[instance].element.hasOwnProperty('form')) {
          if (Drupal.ajax.instances[instance].element.form.classList.contains(Drupal.votingApiReaction.className)) {
            Drupal.ajax.instances[instance].beforeSend = Drupal.votingApiReaction.beforeSend;
          }
        }
      }

    }
  };

  // Disable radios before AJAX
  Drupal.votingApiReaction.beforeSend = function (xmlhttprequest, options) {
    Drupal.Ajax.prototype.beforeSend(xmlhttprequest, options);

    $("input[type=radio]:not(:disabled)", this.element.form).attr("disabled", true);
  };

})(jQuery);
