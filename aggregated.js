/**
 * aggregated.js
 * Author: Hiroyoshi Kurohara(Microgadget,inc.)
 * Author E-Mail: kurohara@yk.rim.or.jp
 * License: GPLv2 or Lator.
 */

jQuery(function($) {

function ajaxarg(arg, objs) {
  $(objs[0]).addClass("loading");
  $(objs[1]).addClass("waiting");
  $(objs[2]).prop("disabled", true);

  var success_old = arg.success;
  var error_old = arg.error;

  return {
    url: arg.url,
    type: arg.type,
    data: arg.data,
    timeout: arg.timeout,
    success: function(data, status, jqXHR) {
      try {
        success_old(data, status, jqXHR);
      } catch (e) {}
      $(objs[0]).removeClass("loading");
      $(objs[1]).removeClass("waiting");
      $(objs[2]).prop("disabled", false);
    },
    error: function(jqXHR, textStatus, errorThrown) {
      try {
        error_old(jqXHR, textStatus, errorThrown);
      } catch (e) {}
      $(objs[0]).removeClass("loading");
      $(objs[1]).removeClass("waiting");
      $(objs[2]).prop("disabled", false);
    },
  }
}

function get_aggregated_content(pn_start, pn_current) {

  var ajaxData = { 
        action: 'questionnaire_aggregated_content', 
        postid: questionnaire_data.postid,
        nonce: questionnaire_data.nonce,
        start: pn_start,
        current: pn_current,
  };
  $.ajax(
    ajaxarg(
    {
      url: questionnaire_data.admin_ajax_url,
      type: 'POST',
      data: ajaxData,
      timeout: 5000,
      success: function(data, status) { 
        $("div.aggregated").html(data);
        setuplinks();
      } ,
      error: function() { 
      }
    }
    , ["div.aggregated", "table.aggregated", ""])
  );

}

function setuplinks() {
  try {
    $("a.previndex").click(function () {
      get_aggregated_content($(event.currentTarget).attr('start'), $(event.currentTarget).attr('current'));
      return false;
    });

    $("a.prevlist").on('click', function() {
      get_aggregated_content($(event.currentTarget).attr('start'), $(event.currentTarget).attr('current'));
      return false;
    });

    $("a.pageindex").on('click', function() {
      get_aggregated_content($(event.currentTarget).attr('start'), $(event.currentTarget).attr('current'));
      return false;
    });

    $("a.nextlist").on('click', function() {
      get_aggregated_content($(event.currentTarget).attr('start'), $(event.currentTarget).attr('current'));
      return false;
    });

    $("a.nextindex").on('click', function() {
      get_aggregated_content($(event.currentTarget).attr('start'), $(event.currentTarget).attr('current'));
      return false;
    });

  } catch (e) {
  }
}

$(document).ready(function() {
  get_aggregated_content(1, 1);
});

});
