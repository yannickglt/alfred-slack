(function (window) {

  var REDIRECT_URI = 'http://yannickglt.github.io/alfred-slack/';
  var OAUTH_URL = '//__TEAM__.slack.com/oauth?client_id=__CLIENT_ID__&scope=channels%3Ahistory+channels%3Awrite+channels%3Aread+groups%3Ahistory+groups%3Aread+groups%3Awrite+files%3Aread+files%3Awrite%3Auser+im%3Ahistory+im%3Aread+im%3Awrite+search%3Aread+stars%3Aread+team%3Aread+users%3Aread+users%3Awrite&team=1&redirect_uri=__REDIRECT_URI__';

  window.addEventListener('load', function () {

    var step = (getParameterByName('code') !== null) ? 2 : 1;
    if (step === 1) {
      document.getElementById('step-1').style.display = 'block';
      document.getElementById('step-2').style.display = 'none';
    } else {
      document.getElementById('step-1').style.display = 'none';
      document.getElementById('step-2').style.display = 'block';
      fillCode();
    }

    var clipboard = new Clipboard('#code-btn');
    clipboard.on('success', function (e) {
      e.clearSelection();
      showTooltip(e.trigger, 'Copied!');
    });
    document
      .getElementById('code-btn')
      .addEventListener('mouseleave', function (e) {
        e.currentTarget.setAttribute('class', 'btn');
        e.currentTarget.removeAttribute('aria-label');
      });
  });

  function showTooltip(elem, msg) {
    elem.setAttribute('class', 'btn tooltipped tooltipped-s');
    elem.setAttribute('aria-label', msg);
  }

  function generateCode() {
    var team = document.getElementById('team').value.toLowerCase();
    var clientId = document.getElementById('client_id').value;
    var redirectUrl = encodeURIComponent(REDIRECT_URI + '?client_id=' + clientId);
    var url = OAUTH_URL.replace(/__TEAM__/g, team).replace(/__CLIENT_ID__/g, clientId).replace(/__REDIRECT_URI__/g, redirectUrl);
    window.open(url, '_self');
  }

  function fillCode() {
    var code = getParameterByName('client_id') + '|' + getParameterByName('code');
    document.getElementById('code').value = code;
  }

  function getParameterByName(name, url) {
    if (!url) {
      url = window.location.href;
    }
    name = name.replace(/[\[\]]/g, '\\$&');
    var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
      results = regex.exec(url);
    if (!results) {
      return null;
    }
    if (!results[2]) {
      return '';
    }
    return decodeURIComponent(results[2].replace(/\+/g, ' '));
  }

  window.alfredSlack = {
    generateCode: generateCode
  };

})(window);
