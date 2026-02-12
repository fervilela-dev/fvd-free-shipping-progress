(function($){
  function refreshBar($bar){
    if(!$bar || !$bar.length) return;

    var context = $bar.data('context') || 'default';
    var goal = $bar.data('goal') || ''; // por si usas override en el futuro

    $.ajax({
      url: (window.FVD_FREESHIP && FVD_FREESHIP.ajaxUrl) ? FVD_FREESHIP.ajaxUrl : '',
      method: 'POST',
      dataType: 'json',
      data: {
        action: (window.FVD_FREESHIP && FVD_FREESHIP.action) ? FVD_FREESHIP.action : 'fvd_freeship_fragment',
        nonce: (window.FVD_FREESHIP && FVD_FREESHIP.nonce) ? FVD_FREESHIP.nonce : '',
        context: context,
        goal: goal
      }
    }).done(function(res){
      if(res && res.success && res.data && res.data.html){
        // Reemplaza SOLO el bloque actual (soporta múltiples barras en la página)
        $bar.replaceWith(res.data.html);
      }
    });
  }

  function refreshAll(){
    $('.fvd-freeship-bar').each(function(){
      refreshBar($(this));
    });
  }

  // Woo events que suelen dispararse al actualizar carrito/mini-cart
  $(document.body).on('added_to_cart removed_from_cart updated_cart_totals updated_wc_div wc_fragments_refreshed', function(){
    refreshAll();
  });

  // Primer render: por si el cache del fragment dejó valores viejos
  $(function(){ refreshAll(); });

})(jQuery);
