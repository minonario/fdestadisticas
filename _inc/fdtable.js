jQuery(function ($) {
  
  jQuery.fn.extend({
  ot_face: function() {
          var SHARE_URL = "https://www.facebook.com/sharer/sharer.php?";
          jQuery(".ot-face").each(function() {
                  var elem = jQuery(this),
                  // Use current page URL as default link
                  url = (elem.attr("data-url") || document.location.href);

                  // Set href to tweet page
                  elem.attr({
                          href: SHARE_URL + "u=" +
                                          url , //encodeURIComponent(document.location.href) +
                          target: "_blank"
                  });
          });	
      }
  });
  
  jQuery.fn.extend({
  ot_tweet: function() {
          var TWEET_URL = "https://twitter.com/intent/tweet";
          this.each(function() {
                  var elem = jQuery(this),
                  // Use current page URL as default link
                  url = encodeURIComponent(elem.attr("data-url") || document.location.href),
                  // Use page title as default tweet message
                  text = elem.attr("data-text") || document.title,
                  via = elem.attr("data-via") || "",
                  related = encodeURIComponent(elem.attr("data-related")) || "",
                  hashtags = encodeURIComponent(elem.attr("data-hashtags")) || "";
                  // Set href to tweet page
                  elem.attr({
                          href: TWEET_URL + "?original_referer=" +
                                          url + //encodeURIComponent(document.location.href) +
                                          "&source=tweetbutton&text=" + text + "&url=" + url + "&via=" + via,
                          target: "_blank"
                  });
          });
      }
  });
  
  jQuery.fn.extend({
  ot_link: function() {
          var SHARE_URL = "http://www.linkedin.com/shareArticle?mini=true";
          jQuery(".ot-link").each(function() {
                  var elem = jQuery(this),
                  // Use current page URL as default link
                  url = (elem.attr("data-url") || document.location.href),
                  text = elem.attr("data-title") || document.title;
                  // Set href to tweet page
                  elem.attr({
                          href: SHARE_URL + "&url=" +
                                          url + //encodeURIComponent(document.location.href) +
                                          "&title=" + text,
                          target: "_blank"
                  });
          });	
      }
  });
  
  var table;
  var pais = $('#fdpais').val();
  var formato = $('#fdformato').val();
  var tipo = $('#fdtipo').val();
  var html = $('#fdindicador').val();
  
  jQuery.ajax({
    type: "GET",
    url: fdtable.ajaxurl,
    data: {action: 'fdtable_action',
      pais: pais,
      formato: formato,
      tipo: tipo,
      html: html,
      security: fdtable.security
    },    
    cache: false,
    dataType: "json",
    beforeSend: function () {
      $('.main-fd').find('.visible').removeClass('oculto');
      $('.tablatitulo').html('');
    },
    success: function (data) {
      
      $('.render_table').html(data.data);
        $('.main-fd').find('.visible').addClass('oculto');
        var matchdos = $('.tablefinanzas').hasClass('matchdos');
        if (matchdos){
          var ordercols = [3,5,7];
        }else{
          var ordercols = [3,4,6,9];
        }
        
      table = $('.tablefinanzas').DataTable({
        destroy: true,
        bInfo: false,
        bFilter: false,
        paging: false,
        scrollX: true,
        fixedHeader: true,
        order: [],
        "columnDefs": [ 
            { "targets": 0, "searchable": false},
            { "targets": [1], "orderable": false },
            { "targets": ordercols, type: 'formatted-num'}
         ]
      });
      
      // ADD TITLE .main-fd - data-title2 - data-foo
      $('.tablatitulo').html("<h2>"+$('#fdindicador option:selected').attr("data-foo")+"</h2>");
      $('.tablatitulo').append("<h2>"+$('#fdindicador option:selected').attr("data-title2")+"</h2>");
      
      $('.main-fd').find('.visible').addClass('oculto');
      $pais_url = $('#fdpais').find(':selected').attr('data-url');
      $formato_url = $('#fdformato').find(':selected').attr('data-url');
      $fdtipo_url = $('#fdtipo').find(':selected').attr('data-url');
      var url = fdtable.baseslug + $pais_url.toLowerCase()+'/'+$formato_url.toLowerCase()+'/'+$fdtipo_url.toLowerCase()+'/tabla/'+html.toLowerCase();
      
      window.history.pushState("object or string", "Title", "/"+ url);
      //FEATURE - Share Social Networks
      document.querySelector('meta[property="og:url"]').setAttribute("content", window.location.href);
      document.querySelector('meta[property="og:title"]').setAttribute("content", html );
      document.title = html + ' - Finanzas Digital';
      
      if ( !fdtable.is_sassy_active ) {
        jQuery('.ot-tweet').ot_tweet();
        jQuery('.ot-link').ot_link();
        jQuery('.ot-face').ot_face();
      }else{
        var _url = $('.heateor_sss_button_twitter').attr('href');
        _url = _url.split("?")[0]+"?text"+document.title+"&url="+window.location.href;
        $('.heateor_sss_button_twitter').attr('href',_url);
        
        _url = $('.heateor_sss_facebook').attr('href');
        _url = _url.split("?")[0]+"?u="+window.location.href;
        $('.heateor_sss_facebook').attr('href',_url);
        
        _url = $('.heateor_sss_button_linkedin').attr('href');
        _url = _url.split("?")[0]+"?mini=true&url="+window.location.href+"&title="+document.title;
        $('.heateor_sss_button_linkedin').attr('href',_url);
        
        _url = $('.heateor_sss_whatsapp').attr('href');
        _url = _url.split("?")[0]+"?text="+document.title+" "+window.location.href;
        $('.heateor_sss_whatsapp').attr('href',_url);
        
      }

    }
  });
  
  jQuery(document).ready(function(jQuery){
          jQuery('.ot-link, .ot-tweet, .ot-face').click(function(event) {
                  event.preventDefault();
                  var width  = 575,
                          height = 400,
                          left   = (jQuery(window).width()  - width)  / 2,
                          top    = (jQuery(window).height() - height) / 2,
                          url    = this.href,
                          opts   = 'status=1' +
                                           ',width='  + width  +
                                           ',height=' + height +
                                           ',top='    + top    +
                                           ',left='   + left;

                  window.open(url, 'twitter', opts);

                  return false;
          });
          
  });

  jQuery('.main-fd select').change(function () {

    var selection = $(this).attr('id');
    var pais = ($(this).attr('id') === 'fdpais' ? $(this).val() : $('#fdpais').val());
    var formato = ($(this).attr('id') === 'fdformato' ? $(this).val() : $('#fdformato').val());
    var tipo = ($(this).attr('id') === 'fdtipo' ? $(this).val() : $('#fdtipo').val());
    var html = ($(this).attr('id') === 'fdindicador' ? $(this).val() : $('#fdindicador').val());

    jQuery.ajax({
      type: "GET",
      url: fdtable.ajaxurl,
      data: {action: 'fdtable_action',
        selection: selection,
        pais: pais,
        formato: formato,
        tipo: tipo,
        html: html,
        security: fdtable.security
      },
      cache: false,
      dataType: "json",
      beforeSend: function () {
        $('.main-fd').find('.visible').removeClass('oculto');
        $('.render_table').html('');
        $('.tablatitulo').html('');
      },
      success: function (data) {
        if (data.combos != null) {
          $.each(data.combos, function (i, item) {
            var select = $('#' + item.id).empty();
            $.each(item.combo, function (i, x) {
              select.append('<option data-foo="'+x.title+'" data-title2="'+x.datafoo+'" value="'
                      + x.value
                      + '"' + (x.value == item.selected ? 'selected' : '') + ' data-url="'+x.dataurl+'" >'
                      + x.title
                      + '</option>');
            });
          });
        }

        $('.render_table').html(data.data);
        $('.main-fd').find('.visible').addClass('oculto');
        var matchdos = $('.tablefinanzas').hasClass('matchdos');
        if (matchdos){
          var ordercols = [3,5,7];
        }else{
          var ordercols = [3,4,6,9];
        }
        
        table = $('.tablefinanzas').DataTable({
          destroy: true,
          bInfo: false,
          bFilter: false,
          paging: false,
          scrollX: true,
          order: [],
          "columnDefs": [ 
            { "targets": 0, "searchable": false},
            { "targets": [1], "orderable": false },
            { "targets": ordercols, type: 'formatted-num'}
        ]
        });
        
        new $.fn.dataTable.FixedHeader( table );
        
        $pais_url = $('#fdpais').find(':selected').attr('data-url');
        $formato_url = $('#fdformato').find(':selected').attr('data-url');
        $fdtipo_url = $('#fdtipo').find(':selected').attr('data-url');
        var html = $('#fdindicador').val();
        var url = fdtable.baseslug + $pais_url.toLowerCase()+'/'+$formato_url.toLowerCase()+'/'+$fdtipo_url.toLowerCase()+'/tabla/'+html.toLowerCase();

if (typeof ga === 'function') {
		gtag('event', 'page_view', {
  'page_location': url,
  'page_title': html + ' - Finanzas Digital'
});
}

        window.history.pushState("object or string", "Title", "/"+ url);
        
        document.querySelector('meta[property="og:url"]').setAttribute("content", window.location.href);
        document.querySelector('meta[property="og:title"]').setAttribute("content", html );
        document.title = html + ' - Finanzas Digital';

        $('.tablatitulo').html("<h2>"+$('#fdindicador option:selected').attr("data-foo")+"</h2>");
        $('.tablatitulo').append("<h2>"+$('#fdindicador option:selected').attr("data-title2")+"</h2>");
        
        if ( !fdtable.is_sassy_active ) {
          jQuery('.ot-tweet').ot_tweet();
          jQuery('.ot-link').ot_link();
          jQuery('.ot-face').ot_face();
        }else{
          var _url = $('.heateor_sss_button_twitter').attr('href');
          _url = _url.split("?")[0]+"?text"+document.title+"&url="+window.location.href;
          $('.heateor_sss_button_twitter').attr('href',_url);

          _url = $('.heateor_sss_facebook').attr('href');
          _url = _url.split("?")[0]+"?u="+window.location.href;
          $('.heateor_sss_facebook').attr('href',_url);

          _url = $('.heateor_sss_button_linkedin').attr('href');
          _url = _url.split("?")[0]+"?mini=true&url="+window.location.href+"&title="+document.title;
          $('.heateor_sss_button_linkedin').attr('href',_url);

          _url = $('.heateor_sss_whatsapp').attr('href');
          _url = _url.split("?")[0]+"?text="+document.title+" "+window.location.href;
          $('.heateor_sss_whatsapp').attr('href',_url);

        }
        
      }
    });
  });
  
  window.addEventListener('popstate', function(event) {
      // The popstate event is fired each time when the current history entry changes.
          window.location = window.location.href;
  }, false);
  
});