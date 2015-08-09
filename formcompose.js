/**
 * formcompose.js
 * part of questionnaire app.
 * Author: Hiroyoshi Kurohara(Microgadget,inc.)
 * Author EMail: kurohara@yk.rim.or.jp
 * License: GPLv2 or Lator
 */
jQuery(function($) {

function deleteItem() {
  event.currentTarget.parentNode.parentNode.removeChild(event.currentTarget.parentNode);
  checkForm();
  genjson();
  showSampleForm(metajson());
}

function moveup() {
  var tgt = $(event.currentTarget.parentNode);
  $(tgt.prop('previousSibling')).before(tgt);
  checkForm();
  genjson();
  showSampleForm(metajson());
}

function movedn() {
  var tgt = $(event.currentTarget.parentNode);
  $(tgt.prop('nextSibling')).after(tgt);
  checkForm();
  genjson();
  showSampleForm(metajson());
}

function selectFirstEnabled(select) {
  if (select.find("option:selected").prop("disabled")) {
    select.find('option:not(:disabled):first').prop("selected", true);
  }
}
function checkForm(event) {
  var isFirst = true;
  var isAfterLabel = false;
  var prevOption;
  function doDisable(item, bDisable) {
    item.disabled = bDisable;
  }
  $("#questionnaire_composeForm .propItem").each(function() {
    var select = $(this).find("select");
    if (isFirst || prevOption === 'TEXT') {
      isFirst = false;
      select.find("option").each(function() { doDisable(this, true); });
      select.find("option.optionLabel").each(function() { doDisable(this, false); });
      isAfterLabel = true;
      selectFirstEnabled(select);
    } else if (isAfterLabel) {
      select.find("option").each(function() { doDisable(this,false); });
      select.find("option.optionLabel").each(function() { doDisable(this, true); });
      isAfterLabel = false;
      selectFirstEnabled(select);
    } else {
      select.find("option").each(function() { doDisable(this, true); });
      select.find("option.optionLabel").each(function() { doDisable(this, false); });
      select.find('option[value="'+prevOption+'"]').each(function() { doDisable(this, false); });
      selectFirstEnabled(select);
      var thisOption = select.find("option:selected").attr("value");
      if (thisOption === 'LBL') {
        isAfterLabel = true;
      }
    }
    prevOption = select.find("option:selected").attr("value");
  });

}

function iterate_metajson(jsonobj, f) {
  for (var i = 0;i < jsonobj.length;) {
    var text;
    var option;
    for (var j = 0;j < 2;++j) {
      if (jsonobj[i+j].name === 'text') text = jsonobj[i+j].value;
      if (jsonobj[i+j].name === 'select') option = jsonobj[i+j].value;
    }
    i += 2;
    f(option, text);
  }
}


function showSampleForm(formMeta) {
  var option;
  var text;
  var itemId = 0;
  var fset;
  var item;
  var testForm = $("#questionnaire_actForm");
  testForm.find("*").remove();
  iterate_metajson(formMeta, function(option, text) {

    switch (option) {
    case 'LBL':
      fset = $('<fieldset><legend>'+text+'</legend></fieldset>');
      testForm.append(fset);
      item = undefined;
      break;
    case 'TEXT':
      if (!item) {
        ++itemId;
        item = $('<textarea name="message_' + itemId +'" placeholder="'+text+'"></textarea>');
        fset.append(item);
      }
      break;
    case 'OPT':
      if (!item) {
        ++itemId;
        item = $('<select name="select_' + itemId + '"></select>');
        optionId = 0;
        fset.append(item);
      }
      var option = $('<option value="'+optionId+'">'+text+'</option>');
      item.append(option);
      ++optionId;
      break;
    case 'RAD':
      if (fset.find("input").length == 0) {
        optionId = 0;
        ++itemId;
      }
      var inputName = "radio_" + itemId;
      var inputId = inputName + "_" + optionId;
      item = $('<input type="radio" name="' + inputName + '" value="' + optionId + '" id="' + inputId + '">');
      label = $('<label for="' + inputId + '">' + text + '</label><br>');
      fset.append(item);
      fset.append(label);
      ++optionId;
      break;
    case 'CHK':
      if (fset.find("input").length == 0) {
        optionId = 0;
        ++itemId;
      }
      var inputName = "check_" + itemId;
      var inputId = inputName + "_" + optionId;
      item = $('<input type="checkbox" name="' + inputName + '" value="' + optionId + '" id="' + inputId + '">');
      label = $('<label for="' + inputId + '">' + text + '</label><br>');
      fset.append(item);
      fset.append(label);
      ++optionId;
      break;
    default:
      break;
    }
  });
}

function selectChanged() {
  checkForm(event);
  genjson();
  showSampleForm(metajson());
}

function genjson() {
  $("#questionnaire_metajson").val(JSON.stringify(
    $("#questionnaire_composeForm").serializeArray()
  ));
}

function metajson() {
  var jsonstr = $("#questionnaire_metajson").val();
  if (!jsonstr || jsonstr === '') {
    jsonstr = "[]";
  }
  return JSON.parse(jsonstr);
}

function initForm() {
  $("div.questionnaire_composeForm form").remove();
  $("div.questionnaire_actForm form").remove();

  $("div.questionnaire_composeForm").append($('<form id="questionnaire_composeForm"></form>'));
  $("div.questionnaire_actForm").append($('<form id="questionnaire_actForm"></form>'));
  
  iterate_metajson(metajson(), function(opt, text) {
    itemadd(opt, text);
  });
  checkForm();
  showSampleForm(metajson());
}

function itemadd(opt, text) {
    var select = $('<select class="selectItemClass formItem"></select>');
    select.change(selectChanged);
    select.prop('name', 'select');
    select.append('<option class="optionLabel formItem" value="LBL">Label</option>');
    select.append('<option class="optionMessage formItem" value="TEXT">Message</option>');
    select.append('<option class="optionOption formItem" value="OPT">Option</option>');
    select.append('<option class="optionRadio formItem" value="RAD">Radio</option>');
    select.append('<option class="optionCheck formItem" value="CHK">Check</option>');
    if (opt) {
      select.find('option[value="' + opt + '"]').prop("selected", true);
    }
    var delbtn = $('<button type="button" class="deleteme"><span class="icon-cross"></span></button>');
    delbtn.click(deleteItem);
    var upbtn = $('<button type="button" class="moveup"><span class="icon-move-up"></span></button>');
    upbtn.click(moveup);
    var dnbtn = $('<button type="button" class="movedn"><span class="icon-move-down"></span></button>');
    dnbtn.click(movedn);
    var item = $('<div class="propItem"></div>');
    var textBox = $('<input type="text" class="forLabel formItem">');
    textBox.prop("value", " ");
    textBox.prop('name', 'text');
    if (text) {
      textBox.prop("value", text);
    }
    textBox.change(function() {
      this.value = this.value.replace(/</g, '&lt;').replace(/>/g, '&gt;');
      genjson();
      showSampleForm(metajson());
    });
    item.append(delbtn);
    item.append(upbtn);
    item.append(dnbtn);
    item.append(select);
    item.append(textBox);
    $("#questionnaire_composeForm").append(item);
}

function setfrmvalue(actfrmvalue) {
  var i = 0;
  if (actfrmvalue.length > 0) {
    $("#questionnaire_actForm fieldset").each(function() {
      try {
        if ($(this).find('input:checkbox').size() > 0) {
          for (;i < actfrmvalue.length;) {
            var checklist = $(this).find('input:checkbox[name="' + actfrmvalue[i].name + '"][value="' + actfrmvalue[i].value + '"]');
            if (checklist.size() > 0) {
              checklist.attr("checked", true);
            } else {
              break;
            }
            ++i;
          }
        } else {
          $(this).find("textarea").val(actfrmvalue[i].value);
          $(this).find('option[value="' + actfrmvalue[i].value + '"]').select();
          $(this).find('input:radio[value="' + actfrmvalue[i].value + '"]').attr("checked", true);
          ++i;
        }
      } catch (e) {
        console.log(e);
      }
  
    });
  }
  $("#questionnaire_actForm").change(function() {
    $("div.questionnaire_status").text("");
  });
}

function ajaxarg(arg, objs) {
  $(objs[0]).addClass("loading");
  $(objs[1]).addClass("waiting");
  $(objs[2]).prop("disabled", true);

  var success_old = arg.success;
  var error_old = arg.error;
  arg.success = function(data, status, jqXHR) {
      try {
        success_old(data, status, jqXHR);
      } catch (e) {}
      $(objs[0]).removeClass("loading");
      $(objs[1]).removeClass("waiting");
      $(objs[2]).prop("disabled", false);
    };
  arg.error = function(jqXHR, textStatus, errorThrown) {
      try {
        error_old(jqXHR, textStatus, errorThrown);
      } catch (e) {}
      $(objs[0]).removeClass("loading");
      $(objs[1]).removeClass("waiting");
      $(objs[2]).prop("disabled", false);
    };

  return arg;
}

function showCenter(jqobj) {
  jqobj.toggle(400);
  var parent = jqobj.parent();
  var left = parent.offset().left + (parent.width() - 200) / 2;
  var top = parent.offset().top + (parent.height() - 100) / 2;
  jqobj.offset({"left": left, "top": top});
}

function showSuccessDialog(bShow, msg) {
  if ( (bShow && $("div.questionnaire_dialog:visible").size() === 0 ) ||
      (!bShow && $("div.questionnaire_dialog:visible").size() > 0 )) {
      showCenter($("div.questionnaire_dialog"))
  }

  $("div.questionnaire_dialog div.message").text(msg);
}

function showErrorDialog(bShow, msg) {
  if ( (bShow && $("div.questionnaire_dialog:visible").size() === 0 ) ||
      (!bShow && $("div.questionnaire_dialog:visible").size() > 0 )) {
      showCenter($("div.questionnaire_dialog"))
  }

  $("div.questionnaire_dialog div.message").text(msg);

}

$(document).ready(function() {
  initForm();
  $("#questionnaire_addItem").on("click", function() {
    itemadd();
    checkForm();
    genjson();
    showSampleForm(metajson());
  });

  $("#questionnaire_clearAnswers").on("click", function() {
    var ajaxData = { 
      action: 'questionnaire_clear_answers', 
      postid: questionnaire_data.postid,
      nonce: questionnaire_data.nonce
    };
    $.ajax(ajaxarg(
      {
        url: questionnaire_data.admin_ajax_url,
        type: 'POST',
        data: ajaxData,
        timeout: 5000,
        success: function(data, status) {
          var jsobj = JSON.parse(data);
          $(".questionnaire_answerinfo span:nth-child(2)").text(jsobj['count']);
        },
        error: function() {
        },
      },
      [ "div.questionnaire_answerinfo", "div.questionnaire_answerinfo table", "#questionnaire_clearAnswers"])
    );

  });

  $("div.questionnaire_answersheet").on("click", function() {
    if ($("div.questionnaire_dialog:visible").size() > 0) {
      $("div.questionnaire_dialog").toggle(400);
    }
  });

  try {
    if (questionnaire_data.showForm) {
      showSampleForm(JSON.parse(questionnaire_data.metajsonstr, questionnaire_data.frmvalue));
      setfrmvalue(JSON.parse(questionnaire_data.frmvalue));
      $("#questionnaire_doAnswerBtn").on("click", function() {
        var ansObj = $("#questionnaire_actForm").serializeArray();
        var author_name = $("#questionnaire_name").val();
        var author_email = $("#questionnaire_mail").val();
        var ansVal = JSON.stringify(ansObj);
        var ajaxData = { 
          action: 'questionnaire_postanswer', 
          frmdata: ansVal, 
          postid: questionnaire_data.postid, 
          author: author_name, 
          email: author_email,
          nonce: questionnaire_data.nonce
        };
        showSuccessDialog(false, "");
        $.ajax(ajaxarg({
          url: questionnaire_data.admin_ajax_url,
          type: 'POST',
          data: ajaxData,
          timeout: 5000,
          success: function(data, status) { 
            try {
              resultObj = JSON.parse(data);
              if (resultObj.success) {
                showSuccessDialog(true, questionnaire_data.txtThankYou);
              } else {
                showErrorDialog(true, resultObj.msg);
              }
            } catch (e) {
              $("div.questionnaire_status").text(data);
            }
          } ,
          error: function() { 
            $("div.questionnaire_status").text(questionnaire_data.txtServerError);
          }
        }, ["div.questionnaire_answersheet", "div.questionnaire_answersheet div", "#questionnaire_doAnswerBtn"] )
        );
      });
    }
  } catch (e) {
  }

});

});
