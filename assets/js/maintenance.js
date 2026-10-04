/* Simple decorative animation for the maintenance card */
(function($){
    $(function(){
        var $card = $('.im-maintenance-card');
        if( !$card.length ) return;
        // gentle up/down loop using CSS-friendly transforms
        var t = 0;
        setInterval(function(){
            t = (t+1) % 360;
            var y = Math.sin(t * Math.PI / 180) * 6; // -6..6 px
            $card.css('transform','translateY(' + y + 'px)');
        }, 80);
    });
})(jQuery);
