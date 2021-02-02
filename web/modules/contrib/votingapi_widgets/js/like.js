/**
 * @file
 * Attaches like rating.
 */

(function ($, Drupal) {
  Drupal.behaviors.likeRating = {
    attach: function (context, settings) {
     $('body').find('.like').each(function () {
       var $this = $(this);
       $(this).find('select').once('processed').each(function () {
         $this.find('[type=submit]').hide();
         var $select = $(this);
         var isPreview = $select.data('is-edit');
         $select.after('<div class="like-rating"><a href="#"><i class="fa fa-thumbs-up"></i></a></div>').hide();
         $this.find('.like-rating a').eq(0).each(function () {
           $(this).bind('click',function (e) {
             if (isPreview) {
               return;
             }
             e.preventDefault();
             $select.get(0).selectedIndex = 0;
             $this.find('[type=submit]').trigger('click');
             $this.find('a').addClass('disabled');
           })
         })
       })
     });
    }
  };
})(jQuery, Drupal);
