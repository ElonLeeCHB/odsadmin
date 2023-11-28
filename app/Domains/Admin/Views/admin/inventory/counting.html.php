<!DOCTYPE html>
<html dir="ltr" lang="en">
  <head>
    <meta charset="UTF-8"/>
    <title>訂單系統</title>
    <base href="http://sysdev.chinabing.test/backend/"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
    <meta http-equiv="cache-control" content="no-cache">
    <meta http-equiv="expires" content="0">
    <meta name="csrf-token" content="0p9V7ggP6fNKmVNIbyezGEiyYC7QNI8wbPReUSxs" />
    <link  href="http://sysdev.chinabing.test/backend/assets-admin/stylesheet/bootstrap.css" rel="stylesheet" media="screen"/>
    <link  href="http://sysdev.chinabing.test/backend/assets-admin/stylesheet/fonts/fontawesome/css/all.min.css" rel="stylesheet" type="text/css"/>
    <link  href="http://sysdev.chinabing.test/backend/assets-admin/stylesheet/stylesheet.css" rel="stylesheet" type="text/css"/>
    <script src="http://sysdev.chinabing.test/backend/assets-admin/javascript/jquery/jquery-3.6.1.min.js" type="text/javascript"></script>
    <script src="http://sysdev.chinabing.test/backend/assets-admin/javascript/jquery/datetimepicker/moment.min.js" type="text/javascript"></script>
    <script src="http://sysdev.chinabing.test/backend/assets-admin/javascript/jquery/datetimepicker/moment-with-locales.min.js" type="text/javascript"></script>
    <script src="http://sysdev.chinabing.test/backend/assets-admin/javascript/jquery/datetimepicker/daterangepicker.js" type="text/javascript"></script>
    <link  href="http://sysdev.chinabing.test/backend/assets-admin/javascript/jquery/datetimepicker/daterangepicker.css" rel="stylesheet" type="text/css"/>
    <script src="http://sysdev.chinabing.test/backend/assets-admin/javascript/ckeditor/ckeditor.js" type="text/javascript"></script>
    <script src="http://sysdev.chinabing.test/backend/assets-admin/javascript/ckeditor/adapters/jquery.js" type="text/javascript"></script>
    <script src="http://sysdev.chinabing.test/backend/assets-admin/javascript/common.js" type="text/javascript"></script>
    <script src="http://sysdev.chinabing.test/backend/assets/javascript/common.js" type="text/javascript"></script>

    
    
    
    
    <style>
.nav-select {
  margin-top: 10px; /* 根据需要调整垂直位置 */
  margin-left: auto;
  margin-right: auto;
  display: block;
  font-weight: bolder;
  font-size: larger;
}
    </style>
  <link rel='stylesheet' type='text/css' property='stylesheet' href='//sysdev.chinabing.test/backend/_debugbar/assets/stylesheets?v=1697098252&theme=auto' data-turbolinks-eval='false' data-turbo-eval='false'><script src='//sysdev.chinabing.test/backend/_debugbar/assets/javascript?v=1697098252' data-turbolinks-eval='false' data-turbo-eval='false'></script><script data-turbo-eval="false">jQuery.noConflict(true);</script>
<script> Sfdump = window.Sfdump || (function (doc) { doc.documentElement.classList.add('sf-js-enabled'); var rxEsc = /([.*+?^${}()|\[\]\/\\])/g, idRx = /\bsf-dump-\d+-ref[012]\w+\b/, keyHint = 0 <= navigator.platform.toUpperCase().indexOf('MAC') ? 'Cmd' : 'Ctrl', addEventListener = function (e, n, cb) { e.addEventListener(n, cb, false); }; if (!doc.addEventListener) { addEventListener = function (element, eventName, callback) { element.attachEvent('on' + eventName, function (e) { e.preventDefault = function () {e.returnValue = false;}; e.target = e.srcElement; callback(e); }); }; } function toggle(a, recursive) { var s = a.nextSibling || {}, oldClass = s.className, arrow, newClass; if (/\bsf-dump-compact\b/.test(oldClass)) { arrow = '▼'; newClass = 'sf-dump-expanded'; } else if (/\bsf-dump-expanded\b/.test(oldClass)) { arrow = '▶'; newClass = 'sf-dump-compact'; } else { return false; } if (doc.createEvent && s.dispatchEvent) { var event = doc.createEvent('Event'); event.initEvent('sf-dump-expanded' === newClass ? 'sfbeforedumpexpand' : 'sfbeforedumpcollapse', true, false); s.dispatchEvent(event); } a.lastChild.innerHTML = arrow; s.className = s.className.replace(/\bsf-dump-(compact|expanded)\b/, newClass); if (recursive) { try { a = s.querySelectorAll('.'+oldClass); for (s = 0; s < a.length; ++s) { if (-1 == a[s].className.indexOf(newClass)) { a[s].className = newClass; a[s].previousSibling.lastChild.innerHTML = arrow; } } } catch (e) { } } return true; }; function collapse(a, recursive) { var s = a.nextSibling || {}, oldClass = s.className; if (/\bsf-dump-expanded\b/.test(oldClass)) { toggle(a, recursive); return true; } return false; }; function expand(a, recursive) { var s = a.nextSibling || {}, oldClass = s.className; if (/\bsf-dump-compact\b/.test(oldClass)) { toggle(a, recursive); return true; } return false; }; function collapseAll(root) { var a = root.querySelector('a.sf-dump-toggle'); if (a) { collapse(a, true); expand(a); return true; } return false; } function reveal(node) { var previous, parents = []; while ((node = node.parentNode || {}) && (previous = node.previousSibling) && 'A' === previous.tagName) { parents.push(previous); } if (0 !== parents.length) { parents.forEach(function (parent) { expand(parent); }); return true; } return false; } function highlight(root, activeNode, nodes) { resetHighlightedNodes(root); Array.from(nodes||[]).forEach(function (node) { if (!/\bsf-dump-highlight\b/.test(node.className)) { node.className = node.className + ' sf-dump-highlight'; } }); if (!/\bsf-dump-highlight-active\b/.test(activeNode.className)) { activeNode.className = activeNode.className + ' sf-dump-highlight-active'; } } function resetHighlightedNodes(root) { Array.from(root.querySelectorAll('.sf-dump-str, .sf-dump-key, .sf-dump-public, .sf-dump-protected, .sf-dump-private')).forEach(function (strNode) { strNode.className = strNode.className.replace(/\bsf-dump-highlight\b/, ''); strNode.className = strNode.className.replace(/\bsf-dump-highlight-active\b/, ''); }); } return function (root, x) { root = doc.getElementById(root); var indentRx = new RegExp('^('+(root.getAttribute('data-indent-pad') || ' ').replace(rxEsc, '\\$1')+')+', 'm'), options = {"maxDepth":1,"maxStringLength":160,"fileLinkFormat":false}, elt = root.getElementsByTagName('A'), len = elt.length, i = 0, s, h, t = []; while (i < len) t.push(elt[i++]); for (i in x) { options[i] = x[i]; } function a(e, f) { addEventListener(root, e, function (e, n) { if ('A' == e.target.tagName) { f(e.target, e); } else if ('A' == e.target.parentNode.tagName) { f(e.target.parentNode, e); } else { n = /\bsf-dump-ellipsis\b/.test(e.target.className) ? e.target.parentNode : e.target; if ((n = n.nextElementSibling) && 'A' == n.tagName) { if (!/\bsf-dump-toggle\b/.test(n.className)) { n = n.nextElementSibling || n; } f(n, e, true); } } }); }; function isCtrlKey(e) { return e.ctrlKey || e.metaKey; } function xpathString(str) { var parts = str.match(/[^'"]+|['"]/g).map(function (part) { if ("'" == part) { return '"\'"'; } if ('"' == part) { return "'\"'"; } return "'" + part + "'"; }); return "concat(" + parts.join(",") + ", '')"; } function xpathHasClass(className) { return "contains(concat(' ', normalize-space(@class), ' '), ' " + className +" ')"; } a('mouseover', function (a, e, c) { if (c) { e.target.style.cursor = "pointer"; } }); a('click', function (a, e, c) { if (/\bsf-dump-toggle\b/.test(a.className)) { e.preventDefault(); if (!toggle(a, isCtrlKey(e))) { var r = doc.getElementById(a.getAttribute('href').slice(1)), s = r.previousSibling, f = r.parentNode, t = a.parentNode; t.replaceChild(r, a); f.replaceChild(a, s); t.insertBefore(s, r); f = f.firstChild.nodeValue.match(indentRx); t = t.firstChild.nodeValue.match(indentRx); if (f && t && f[0] !== t[0]) { r.innerHTML = r.innerHTML.replace(new RegExp('^'+f[0].replace(rxEsc, '\\$1'), 'mg'), t[0]); } if (/\bsf-dump-compact\b/.test(r.className)) { toggle(s, isCtrlKey(e)); } } if (c) { } else if (doc.getSelection) { try { doc.getSelection().removeAllRanges(); } catch (e) { doc.getSelection().empty(); } } else { doc.selection.empty(); } } else if (/\bsf-dump-str-toggle\b/.test(a.className)) { e.preventDefault(); e = a.parentNode.parentNode; e.className = e.className.replace(/\bsf-dump-str-(expand|collapse)\b/, a.parentNode.className); } }); elt = root.getElementsByTagName('SAMP'); len = elt.length; i = 0; while (i < len) t.push(elt[i++]); len = t.length; for (i = 0; i < len; ++i) { elt = t[i]; if ('SAMP' == elt.tagName) { a = elt.previousSibling || {}; if ('A' != a.tagName) { a = doc.createElement('A'); a.className = 'sf-dump-ref'; elt.parentNode.insertBefore(a, elt); } else { a.innerHTML += ' '; } a.title = (a.title ? a.title+'\n[' : '[')+keyHint+'+click] Expand all children'; a.innerHTML += elt.className == 'sf-dump-compact' ? '<span>▶</span>' : '<span>▼</span>'; a.className += ' sf-dump-toggle'; x = 1; if ('sf-dump' != elt.parentNode.className) { x += elt.parentNode.getAttribute('data-depth')/1; } } else if (/\bsf-dump-ref\b/.test(elt.className) && (a = elt.getAttribute('href'))) { a = a.slice(1); elt.className += ' sf-dump-hover'; elt.className += ' '+a; if (/[\[{]$/.test(elt.previousSibling.nodeValue)) { a = a != elt.nextSibling.id && doc.getElementById(a); try { s = a.nextSibling; elt.appendChild(a); s.parentNode.insertBefore(a, s); if (/^[@#]/.test(elt.innerHTML)) { elt.innerHTML += ' <span>▶</span>'; } else { elt.innerHTML = '<span>▶</span>'; elt.className = 'sf-dump-ref'; } elt.className += ' sf-dump-toggle'; } catch (e) { if ('&' == elt.innerHTML.charAt(0)) { elt.innerHTML = '…'; elt.className = 'sf-dump-ref'; } } } } } if (doc.evaluate && Array.from && root.children.length > 1) { root.setAttribute('tabindex', 0); SearchState = function () { this.nodes = []; this.idx = 0; }; SearchState.prototype = { next: function () { if (this.isEmpty()) { return this.current(); } this.idx = this.idx < (this.nodes.length - 1) ? this.idx + 1 : 0; return this.current(); }, previous: function () { if (this.isEmpty()) { return this.current(); } this.idx = this.idx > 0 ? this.idx - 1 : (this.nodes.length - 1); return this.current(); }, isEmpty: function () { return 0 === this.count(); }, current: function () { if (this.isEmpty()) { return null; } return this.nodes[this.idx]; }, reset: function () { this.nodes = []; this.idx = 0; }, count: function () { return this.nodes.length; }, }; function showCurrent(state) { var currentNode = state.current(), currentRect, searchRect; if (currentNode) { reveal(currentNode); highlight(root, currentNode, state.nodes); if ('scrollIntoView' in currentNode) { currentNode.scrollIntoView(true); currentRect = currentNode.getBoundingClientRect(); searchRect = search.getBoundingClientRect(); if (currentRect.top < (searchRect.top + searchRect.height)) { window.scrollBy(0, -(searchRect.top + searchRect.height + 5)); } } } counter.textContent = (state.isEmpty() ? 0 : state.idx + 1) + ' of ' + state.count(); } var search = doc.createElement('div'); search.className = 'sf-dump-search-wrapper sf-dump-search-hidden'; search.innerHTML = ' <input type="text" class="sf-dump-search-input"> <span class="sf-dump-search-count">0 of 0<\/span> <button type="button" class="sf-dump-search-input-previous" tabindex="-1"> <svg viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1683 1331l-166 165q-19 19-45 19t-45-19L896 965l-531 531q-19 19-45 19t-45-19l-166-165q-19-19-19-45.5t19-45.5l742-741q19-19 45-19t45 19l742 741q19 19 19 45.5t-19 45.5z"\/><\/svg> <\/button> <button type="button" class="sf-dump-search-input-next" tabindex="-1"> <svg viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1683 808l-742 741q-19 19-45 19t-45-19L109 808q-19-19-19-45.5t19-45.5l166-165q19-19 45-19t45 19l531 531 531-531q19-19 45-19t45 19l166 165q19 19 19 45.5t-19 45.5z"\/><\/svg> <\/button> '; root.insertBefore(search, root.firstChild); var state = new SearchState(); var searchInput = search.querySelector('.sf-dump-search-input'); var counter = search.querySelector('.sf-dump-search-count'); var searchInputTimer = 0; var previousSearchQuery = ''; addEventListener(searchInput, 'keyup', function (e) { var searchQuery = e.target.value; /* Don't perform anything if the pressed key didn't change the query */ if (searchQuery === previousSearchQuery) { return; } previousSearchQuery = searchQuery; clearTimeout(searchInputTimer); searchInputTimer = setTimeout(function () { state.reset(); collapseAll(root); resetHighlightedNodes(root); if ('' === searchQuery) { counter.textContent = '0 of 0'; return; } var classMatches = [ "sf-dump-str", "sf-dump-key", "sf-dump-public", "sf-dump-protected", "sf-dump-private", ].map(xpathHasClass).join(' or '); var xpathResult = doc.evaluate('.//span[' + classMatches + '][contains(translate(child::text(), ' + xpathString(searchQuery.toUpperCase()) + ', ' + xpathString(searchQuery.toLowerCase()) + '), ' + xpathString(searchQuery.toLowerCase()) + ')]', root, null, XPathResult.ORDERED_NODE_ITERATOR_TYPE, null); while (node = xpathResult.iterateNext()) state.nodes.push(node); showCurrent(state); }, 400); }); Array.from(search.querySelectorAll('.sf-dump-search-input-next, .sf-dump-search-input-previous')).forEach(function (btn) { addEventListener(btn, 'click', function (e) { e.preventDefault(); -1 !== e.target.className.indexOf('next') ? state.next() : state.previous(); searchInput.focus(); collapseAll(root); showCurrent(state); }) }); addEventListener(root, 'keydown', function (e) { var isSearchActive = !/\bsf-dump-search-hidden\b/.test(search.className); if ((114 === e.keyCode && !isSearchActive) || (isCtrlKey(e) && 70 === e.keyCode)) { /* F3 or CMD/CTRL + F */ if (70 === e.keyCode && document.activeElement === searchInput) { /* * If CMD/CTRL + F is hit while having focus on search input, * the user probably meant to trigger browser search instead. * Let the browser execute its behavior: */ return; } e.preventDefault(); search.className = search.className.replace(/\bsf-dump-search-hidden\b/, ''); searchInput.focus(); } else if (isSearchActive) { if (27 === e.keyCode) { /* ESC key */ search.className += ' sf-dump-search-hidden'; e.preventDefault(); resetHighlightedNodes(root); searchInput.value = ''; } else if ( (isCtrlKey(e) && 71 === e.keyCode) /* CMD/CTRL + G */ || 13 === e.keyCode /* Enter */ || 114 === e.keyCode /* F3 */ ) { e.preventDefault(); e.shiftKey ? state.previous() : state.next(); collapseAll(root); showCurrent(state); } } }); } if (0 >= options.maxStringLength) { return; } try { elt = root.querySelectorAll('.sf-dump-str'); len = elt.length; i = 0; t = []; while (i < len) t.push(elt[i++]); len = t.length; for (i = 0; i < len; ++i) { elt = t[i]; s = elt.innerText || elt.textContent; x = s.length - options.maxStringLength; if (0 < x) { h = elt.innerHTML; elt[elt.innerText ? 'innerText' : 'textContent'] = s.substring(0, options.maxStringLength); elt.className += ' sf-dump-str-collapse'; elt.innerHTML = '<span class=sf-dump-str-collapse>'+h+'<a class="sf-dump-ref sf-dump-str-toggle" title="Collapse"> ◀</a></span>'+ '<span class=sf-dump-str-expand>'+elt.innerHTML+'<a class="sf-dump-ref sf-dump-str-toggle" title="'+x+' remaining characters"> ▶</a></span>'; } } } catch (e) { } }; })(document); </script><style> .sf-js-enabled .phpdebugbar pre.sf-dump .sf-dump-compact, .sf-js-enabled .sf-dump-str-collapse .sf-dump-str-collapse, .sf-js-enabled .sf-dump-str-expand .sf-dump-str-expand { display: none; } .sf-dump-hover:hover { background-color: #B729D9; color: #FFF !important; border-radius: 2px; } .phpdebugbar pre.sf-dump { display: block; white-space: pre; padding: 5px; overflow: initial !important; } .phpdebugbar pre.sf-dump:after { content: ""; visibility: hidden; display: block; height: 0; clear: both; } .phpdebugbar pre.sf-dump span { display: inline; } .phpdebugbar pre.sf-dump a { text-decoration: none; cursor: pointer; border: 0; outline: none; color: inherit; } .phpdebugbar pre.sf-dump img { max-width: 50em; max-height: 50em; margin: .5em 0 0 0; padding: 0; background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAAAAAA6mKC9AAAAHUlEQVQY02O8zAABilCaiQEN0EeA8QuUcX9g3QEAAjcC5piyhyEAAAAASUVORK5CYII=) #D3D3D3; } .phpdebugbar pre.sf-dump .sf-dump-ellipsis { display: inline-block; overflow: visible; text-overflow: ellipsis; max-width: 5em; white-space: nowrap; overflow: hidden; vertical-align: top; } .phpdebugbar pre.sf-dump .sf-dump-ellipsis+.sf-dump-ellipsis { max-width: none; } .phpdebugbar pre.sf-dump code { display:inline; padding:0; background:none; } .sf-dump-public.sf-dump-highlight, .sf-dump-protected.sf-dump-highlight, .sf-dump-private.sf-dump-highlight, .sf-dump-str.sf-dump-highlight, .sf-dump-key.sf-dump-highlight { background: rgba(111, 172, 204, 0.3); border: 1px solid #7DA0B1; border-radius: 3px; } .sf-dump-public.sf-dump-highlight-active, .sf-dump-protected.sf-dump-highlight-active, .sf-dump-private.sf-dump-highlight-active, .sf-dump-str.sf-dump-highlight-active, .sf-dump-key.sf-dump-highlight-active { background: rgba(253, 175, 0, 0.4); border: 1px solid #ffa500; border-radius: 3px; } .phpdebugbar pre.sf-dump .sf-dump-search-hidden { display: none !important; } .phpdebugbar pre.sf-dump .sf-dump-search-wrapper { font-size: 0; white-space: nowrap; margin-bottom: 5px; display: flex; position: -webkit-sticky; position: sticky; top: 5px; } .phpdebugbar pre.sf-dump .sf-dump-search-wrapper > * { vertical-align: top; box-sizing: border-box; height: 21px; font-weight: normal; border-radius: 0; background: #FFF; color: #757575; border: 1px solid #BBB; } .phpdebugbar pre.sf-dump .sf-dump-search-wrapper > input.sf-dump-search-input { padding: 3px; height: 21px; font-size: 12px; border-right: none; border-top-left-radius: 3px; border-bottom-left-radius: 3px; color: #000; min-width: 15px; width: 100%; } .phpdebugbar pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-next, .phpdebugbar pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-previous { background: #F2F2F2; outline: none; border-left: none; font-size: 0; line-height: 0; } .phpdebugbar pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-next { border-top-right-radius: 3px; border-bottom-right-radius: 3px; } .phpdebugbar pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-next > svg, .phpdebugbar pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-input-previous > svg { pointer-events: none; width: 12px; height: 12px; } .phpdebugbar pre.sf-dump .sf-dump-search-wrapper > .sf-dump-search-count { display: inline-block; padding: 0 5px; margin: 0; border-left: none; line-height: 21px; font-size: 12px; }.phpdebugbar pre.sf-dump, .phpdebugbar pre.sf-dump .sf-dump-default{word-wrap: break-word; white-space: pre-wrap; word-break: normal}.phpdebugbar pre.sf-dump .sf-dump-num{font-weight:bold; color:#1299DA}.phpdebugbar pre.sf-dump .sf-dump-const{font-weight:bold}.phpdebugbar pre.sf-dump .sf-dump-str{font-weight:bold; color:#3A9B26}.phpdebugbar pre.sf-dump .sf-dump-note{color:#1299DA}.phpdebugbar pre.sf-dump .sf-dump-ref{color:#7B7B7B}.phpdebugbar pre.sf-dump .sf-dump-public{color:#000000}.phpdebugbar pre.sf-dump .sf-dump-protected{color:#000000}.phpdebugbar pre.sf-dump .sf-dump-private{color:#000000}.phpdebugbar pre.sf-dump .sf-dump-meta{color:#B729D9}.phpdebugbar pre.sf-dump .sf-dump-key{color:#3A9B26}.phpdebugbar pre.sf-dump .sf-dump-index{color:#1299DA}.phpdebugbar pre.sf-dump .sf-dump-ellipsis{color:#A0A000}.phpdebugbar pre.sf-dump .sf-dump-ns{user-select:none;}.phpdebugbar pre.sf-dump .sf-dump-ellipsis-note{color:#1299DA}</style>
</head>
  <body>
    <div id="container">
      <div id="alert" class="toast-container position-fixed top-0 end-0 p-3">
              </div>
      <header id="header" class="navbar navbar-expand navbar-light bg-light">
        <div class="container-fluid">
          <a href="javascript:void(0)" class="navbar-brand d-none d-xxl-block"><img width="180" src="http://sysdev.chinabing.test/backend/assets-admin/image/logo.png" alt="Chinabing" title="Chinabing"></a>

                      <button type="button" id="button-menu" class="btn btn-link d-inline-block d-xxl-none"><i class="fa-solid fa-bars"></i></button>
            <ul class="nav navbar-nav">
              <li class="nav-item dropdown">
                <select class="nav-select form-control" id="input-nav_location_id" name="nav_location_id">
                  
                  <option value="2" selected>和平門市</option>
                </select>
              </li>
              <li id="nav-notification" class="nav-item dropdown">
                <a href="#" data-bs-toggle="dropdown" class="nav-link dropdown-toggle"><i class="fa-regular fa-bell"></i></a>
                <div class="dropdown-menu dropdown-menu-end">
                  <span class="dropdown-item text-center">無資料</span>
                </div>
              </li>
              </li>
              <li id="nav-profile" class="nav-item dropdown">
                <a href="#" data-bs-toggle="dropdown" class="nav-link dropdown-toggle"><img src="http://sysdev.chinabing.test/backend/assets-admin/image/profile.png" alt="" title="" class="rounded-circle"/><span class="d-none d-md-inline d-lg-inline">&nbsp;&nbsp;&nbsp; <i class="fa-solid fa-caret-down fa-fw"></i></span></a>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li><a href="javascript:void(0)" class="dropdown-item"><i class="fa-solid fa-user-circle fa-fw"></i> 我的帳戶</a></li>

                  <li>
                    <hr class="dropdown-divider">
                  </li>
                  <li>
                    <h6 class="dropdown-header">快捷選單</h6>
                  </li>
                  <li><a href="http://sysdev.chinabing.test/backend/zh-Hant/sale/orders" target="_blank" class="dropdown-item"><i class="fa-solid fa-file fa-fw"></i> 訂單作業</a></li>
                  <li><a href="http://sysdev.chinabing.test/backend/zh-Hant/member/members" target="_blank" class="dropdown-item"><i class="fa-solid fa-file fa-fw"></i> 客戶作業</a></li>
                </ul>
              </li>
              <li id="nav-logout" class="nav-item">
                    <a href="http://sysdev.chinabing.test/backend/zh-Hant/logout" class="nav-link" onclick="event.preventDefault();document.getElementById('logout-form').submit();">
                      <i class="fa-solid fa-sign-out"></i> <span class="d-none d-md-inline">Logout</span>
                    </a>
                <form id="logout-form" action="http://sysdev.chinabing.test/backend/zh-Hant/logout" method="POST" class="d-none">
                  <input type="hidden" name="_token" value="0p9V7ggP6fNKmVNIbyezGEiyYC7QNI8wbPReUSxs" autocomplete="off">                  </form>
                  </li>
            </ul>
                  </div>
      </header>
      	<nav id="column-left">
  <div id="navigation"><span class="fas fa-bars"></span> 導覽</div>
  <ul id="menu">
        <li id="menu-dashboard">
            <a href="http://sysdev.chinabing.test/backend/zh-Hant"><i class="fas fa-home"></i> 資訊總覽</a>
                </li>
        <li id="menu-system">
            <a href="#collapse-1" data-bs-toggle="collapse" class="parent collapsed"><i class="fas fa-cog"></i> 商品管理</a>
                  <ul id="collapse-1" class="collapse">
                <li>
                    <a href="http://sysdev.chinabing.test/backend/zh-Hant/catalog/categories?equal_is_active=1"><i class=""></i> 分類</a>
                            </li>
                <li>
                    <a href="http://sysdev.chinabing.test/backend/zh-Hant/catalog/tags?equal_is_active=1"><i class=""></i> 標籤</a>
                            </li>
                <li>
                    <a href="http://sysdev.chinabing.test/backend/zh-Hant/catalog/options?equal_is_active=1"><i class=""></i> 選項</a>
                            </li>
                <li>
                    <a href="http://sysdev.chinabing.test/backend/zh-Hant/catalog/products?equal_is_active=1"><i class=""></i> 商品</a>
                            </li>
              </ul>
          </li>
        <li id="menu-sale">
            <a href="#collapse-2" data-bs-toggle="collapse" class="parent collapsed"><i class="fas fa-user"></i> 銷售管理</a>
                  <ul id="collapse-2" class="collapse">
                <li>
                    <a href="http://sysdev.chinabing.test/backend/zh-Hant/sale/orders"><i class=""></i> 訂單</a>
                            </li>
                <li>
                    <a href="http://sysdev.chinabing.test/backend/zh-Hant/member/members"><i class=""></i> 客戶</a>
                            </li>
                <li>
                    <a href="http://sysdev.chinabing.test/backend/zh-Hant/sale/phrases"><i class=""></i> 常用片語</a>
                            </li>
                <li>
                    <a href="http://sysdev.chinabing.test/backend/zh-Hant/sale/orders/schedule"><i class=""></i> 訂單排程</a>
                            </li>
                <li>
                    <a href="http://sysdev.chinabing.test/backend/zh-Hant/sale/mrequisition/form"><i class=""></i> 備料表</a>
                            </li>
                <li>
                    <a href="http://sysdev.chinabing.test/backend/zh-Hant/sale/mrequisition/setting"><i class=""></i> 備料設定</a>
                            </li>
              </ul>
          </li>
        <li id="menu-common">
            <a href="#collapse-3" data-bs-toggle="collapse" class="parent collapsed"><i class="fas fa-user"></i> 通用設定</a>
                  <ul id="collapse-3" class="collapse">
                <li>
                    <a href="http://sysdev.chinabing.test/backend/zh-Hant/common/taxonomies"><i class=""></i> 詞語屬性</a>
                            </li>
                <li>
                    <a href="http://sysdev.chinabing.test/backend/zh-Hant/common/terms"><i class=""></i> 詞語</a>
                            </li>
                <li>
                    <a href="http://sysdev.chinabing.test/backend/zh-Hant/common/payment_terms"><i class=""></i> 付款條件</a>
                            </li>
                <li>
                    <a href="http://sysdev.chinabing.test/backend/zh-Hant/common/financial_institutions"><i class=""></i> 金融機構設定</a>
                            </li>
              </ul>
          </li>
        <li id="menu-inventory">
            <a href="#collapse-4" data-bs-toggle="collapse" class="parent collapsed"><i class="fas fa-user"></i> 庫存管理</a>
                  <ul id="collapse-4" class="collapse">
                <li>
                    <a href="http://sysdev.chinabing.test/backend/zh-Hant/counterparty/suppliers"><i class=""></i> 廠商設定</a>
                            </li>
                <li>
                    <a href="http://sysdev.chinabing.test/backend/zh-Hant/inventory/warehouses"><i class=""></i> 倉庫設定</a>
                            </li>
                <li>
                    <a href="http://sysdev.chinabing.test/backend/zh-Hant/inventory/units"><i class=""></i> 單位設定</a>
                            </li>
                <li>
                    <a href="http://sysdev.chinabing.test/backend/zh-Hant/inventory/categories"><i class=""></i> 料件分類設定</a>
                            </li>
                <li>
                    <a href="http://sysdev.chinabing.test/backend/zh-Hant/inventory/products"><i class=""></i> 料件設定</a>
                            </li>
                <li>
                    <a href="http://sysdev.chinabing.test/backend/zh-Hant/inventory/boms"><i class=""></i> BOM表</a>
                            </li>
                <li>
                    <a href="http://sysdev.chinabing.test/backend/zh-Hant/inventory/receiving"><i class=""></i> 進貨作業</a>
                            </li>
                <li>
                    <a href="http://sysdev.chinabing.test/backend/zh-Hant/inventory/countings"><i class=""></i> 盤點結餘作業</a>
                            </li>
              </ul>
          </li>
        <li id="menu-setting">
            <a href="#collapse-5" data-bs-toggle="collapse" class="parent collapsed"><i class="fas fa-cog"></i> 系統管理</a>
                  <ul id="collapse-5" class="collapse">
                <li>
                    <a href="http://sysdev.chinabing.test/backend/zh-Hant/setting/settings"><i class=""></i> 參數設定</a>
                            </li>
                <li>
                    <a href="http://sysdev.chinabing.test/backend/zh-Hant/setting/locations"><i class=""></i> 門市設定</a>
                            </li>
                <li>
                    <a href="#collapse-5-2" data-bs-toggle="collapse" class="parent collapsed"><i class=" "></i> 使用者</a>
                              <ul id="collapse-5-2" class="collapse">
                        <li>
                            <a href="http://sysdev.chinabing.test/backend/zh-Hant/setting/user/users?equal_is_admin=1&amp;equal_is_active=1"><i class=" "></i> 使用者</a>
               
                          </li>
                        <li>
                            <a href="http://sysdev.chinabing.test/backend/zh-Hant/setting/user/permissions?equal_is_active=1"><i class=" "></i> 權限</a>
               
                          </li>
                        <li>
                            <a href="/"><i class=" "></i> 角色</a>
               
                          </li>
                      </ul>
                  </li>
              </ul>
          </li>
          </ul>
  <div id="stats">
    <ul>
      <li>
        <div>Orders Completed <span class="float-end">0%</span></div>
        <div class="progress">
          <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%"><span class="sr-only">0%</span></div>
        </div>
      </li>
      <li>
        <div>Orders Processing <span class="float-end">0%</span></div>
        <div class="progress">
          <div class="progress-bar progress-bar-warning" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%"><span class="sr-only">0%</span></div>
        </div>
      </li>
      <li>
        <div>Other Statuses <span class="float-end">0%</span></div>
        <div class="progress">
          <div class="progress-bar progress-bar-danger" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%"><span class="sr-only">0%</span></div>
        </div>
      </li>
    </ul>
  </div>
</nav>

      <div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="float-end">
        <button type="button" data-bs-toggle="tooltip" title="篩選" onclick="$('#filter-term').toggleClass('d-none');" class="btn btn-light d-md-none d-lg-none"><i class="fa-solid fa-filter"></i></button>
        <a id="button-add" href="http://sysdev.chinabing.test/backend/zh-Hant/common/terms/form" data-bs-toggle="tooltip" title="新增" class="btn btn-primary"><i class="fa-solid fa-plus"></i></a>
        <button type="submit" form="form-term" formaction="http://sysdev.chinabing.test/backend/zh-Hant/common/terms/delete" data-bs-toggle="tooltip" title="刪除" onclick="return confirm('確定嗎？');" class="btn btn-danger"><i class="fa-regular fa-trash-can"></i></button>
      </div>
      <h1>詞語</h1>
      <ol class="breadcrumb">
                  <li class="breadcrumb-item"><a href="http://sysdev.chinabing.test/backend/zh-Hant"  >首頁</a></li>
                  <li class="breadcrumb-item"><a href="javascript:void(0)"  style="cursor: default"  >text_product</a></li>
                  <li class="breadcrumb-item"><a href="http://sysdev.chinabing.test/backend/zh-Hant/common/terms"  >詞語</a></li>
              </ol>    </div>
  </div>
  <div class="container-fluid">
    <div class="row">
      <div id="filter-term" class="col-lg-3 col-md-12 order-lg-last d-none d-lg-block mb-3">
        <form>
          <div class="card">
            <div class="card-header"><i class="fa-solid fa-filter"></i> 篩選</div>
            <div class="card-body">

              <div class="mb-3">
                <label class="form-label">詞語屬性</label>
                <input type="text" id="input-taxonomy_name" name="filter_taxonomy_name" value=""  data-oc-target="autocomplete-taxonomy_name" class="form-control" autocomplete="on"/>
                <ul id="autocomplete-taxonomy_name" class="dropdown-menu"></ul>
              </div>

              <div class="mb-3">
                <label class="form-label">識別碼</label>
                <input type="text" id="input-code" name="filter_code" value=""  data-oc-target="autocomplete-name" class="form-control" autocomplete="off"/>
                <ul id="autocomplete-code" class="dropdown-menu"></ul>
              </div>

              <div class="mb-3">
                <label class="form-label">名稱</label>
                <input type="text" id="input-name" name="filter_name" value=""  data-oc-target="autocomplete-name" class="form-control" autocomplete="off"/>
                <ul id="autocomplete-name" class="dropdown-menu"></ul>
              </div>

              <div class="mb-3">
                <label class="form-label">是否啟用</label>
                <select name="equal_is_active" id="input-equal_is_active" class="form-select">
                  <option value="*">--- 請選擇 --- </option>
                  <option value="1" selected>是</option>
                  <option value="0">否</option>
                </select>
              </div>

              <div class="text-end">
                <button type="reset" id="button-clear" class="btn btn-light"><i class="fa fa-refresh" aria-hidden="true"></i> 重設</button>
                <button type="button" id="button-filter" class="btn btn-light"><i class="fa-solid fa-filter"></i> 篩選</button>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="col-lg-9 col-md-12">
        <div class="card">
          <div class="card-header"><i class="fa-solid fa-list"></i> 清單</div>
          <div id="term" class="card-body"><form id="form-term" method="post" data-oc-toggle="ajax" data-oc-load="http://sysdev.chinabing.test/backend/zh-Hant/common/terms/list" data-oc-target="#term">
	<div class="table-responsive">
		<table class="table table-bordered table-hover">
			<thead>
				<tr>
					<td class="text-start"><a href="http://sysdev.chinabing.test/backend/zh-Hant/common/terms/list?sort=id&amp;order=ASC&amp;equal_is_active=1"  class="asc" >ID</a></td>
					<td class="text-start"><a href="http://sysdev.chinabing.test/backend/zh-Hant/common/terms/list?sort=code&amp;order=ASC&amp;equal_is_active=1" >識別碼</a></td>
					<td class="text-start"><a href="http://sysdev.chinabing.test/backend/zh-Hant/common/terms/list?sort=name&amp;order=ASC&amp;equal_is_active=1" >名稱</a></td>
					<td class="text-start"><a href="http://sysdev.chinabing.test/backend/zh-Hant/common/terms/list?sort=taxonomy_name&amp;order=ASC&amp;equal_is_active=1" >詞語屬性名稱</a></td>
					<td class="text-start">是否啟用</td>
					<td class="text-end">操作</td>
				</tr>
			</thead>
			<tbody>
				                <tr>
					<td class="text-start">1387</td>
					<td class="text-start">V</td>
					<td class="text-start">作廢</td>
					<td class="text-start">通用單據狀態</td>
					<td class="text-start">                                  是
                                </td>
                    <td class="text-end"><a href="http://sysdev.chinabing.test/backend/zh-Hant/common/terms/form/1387?sort=id&amp;order=DESC&amp;equal_is_active=1" data-bs-toggle="tooltip" title="Edit" class="btn btn-primary"><i class="fa-solid fa-pencil"></i></a></td>
                </tr>
				                <tr>
					<td class="text-start">1386</td>
					<td class="text-start">C</td>
					<td class="text-start">已確認</td>
					<td class="text-start">通用單據狀態</td>
					<td class="text-start">                                  是
                                </td>
                    <td class="text-end"><a href="http://sysdev.chinabing.test/backend/zh-Hant/common/terms/form/1386?sort=id&amp;order=DESC&amp;equal_is_active=1" data-bs-toggle="tooltip" title="Edit" class="btn btn-primary"><i class="fa-solid fa-pencil"></i></a></td>
                </tr>
				                <tr>
					<td class="text-start">1385</td>
					<td class="text-start">P</td>
					<td class="text-start">未確認</td>
					<td class="text-start">通用單據狀態</td>
					<td class="text-start">                                  是
                                </td>
                    <td class="text-end"><a href="http://sysdev.chinabing.test/backend/zh-Hant/common/terms/form/1385?sort=id&amp;order=DESC&amp;equal_is_active=1" data-bs-toggle="tooltip" title="Edit" class="btn btn-primary"><i class="fa-solid fa-pencil"></i></a></td>
                </tr>
				                <tr>
					<td class="text-start">1384</td>
					<td class="text-start">4</td>
					<td class="text-start">訂單</td>
					<td class="text-start">資料表名稱</td>
					<td class="text-start">                                  是
                                </td>
                    <td class="text-end"><a href="http://sysdev.chinabing.test/backend/zh-Hant/common/terms/form/1384?sort=id&amp;order=DESC&amp;equal_is_active=1" data-bs-toggle="tooltip" title="Edit" class="btn btn-primary"><i class="fa-solid fa-pencil"></i></a></td>
                </tr>
				                <tr>
					<td class="text-start">1383</td>
					<td class="text-start">3</td>
					<td class="text-start">商品料件</td>
					<td class="text-start">資料表名稱</td>
					<td class="text-start">                                  是
                                </td>
                    <td class="text-end"><a href="http://sysdev.chinabing.test/backend/zh-Hant/common/terms/form/1383?sort=id&amp;order=DESC&amp;equal_is_active=1" data-bs-toggle="tooltip" title="Edit" class="btn btn-primary"><i class="fa-solid fa-pencil"></i></a></td>
                </tr>
				                <tr>
					<td class="text-start">1382</td>
					<td class="text-start">2</td>
					<td class="text-start">文章</td>
					<td class="text-start">資料表名稱</td>
					<td class="text-start">                                  是
                                </td>
                    <td class="text-end"><a href="http://sysdev.chinabing.test/backend/zh-Hant/common/terms/form/1382?sort=id&amp;order=DESC&amp;equal_is_active=1" data-bs-toggle="tooltip" title="Edit" class="btn btn-primary"><i class="fa-solid fa-pencil"></i></a></td>
                </tr>
				                <tr>
					<td class="text-start">1381</td>
					<td class="text-start">1</td>
					<td class="text-start">使用者</td>
					<td class="text-start">資料表名稱</td>
					<td class="text-start">                                  是
                                </td>
                    <td class="text-end"><a href="http://sysdev.chinabing.test/backend/zh-Hant/common/terms/form/1381?sort=id&amp;order=DESC&amp;equal_is_active=1" data-bs-toggle="tooltip" title="Edit" class="btn btn-primary"><i class="fa-solid fa-pencil"></i></a></td>
                </tr>
				                <tr>
					<td class="text-start">1380</td>
					<td class="text-start">meat</td>
					<td class="text-start">葷食</td>
					<td class="text-start">葷素食</td>
					<td class="text-start">                                  是
                                </td>
                    <td class="text-end"><a href="http://sysdev.chinabing.test/backend/zh-Hant/common/terms/form/1380?sort=id&amp;order=DESC&amp;equal_is_active=1" data-bs-toggle="tooltip" title="Edit" class="btn btn-primary"><i class="fa-solid fa-pencil"></i></a></td>
                </tr>
				                <tr>
					<td class="text-start">1379</td>
					<td class="text-start">veg</td>
					<td class="text-start">素食</td>
					<td class="text-start">葷素食</td>
					<td class="text-start">                                  是
                                </td>
                    <td class="text-end"><a href="http://sysdev.chinabing.test/backend/zh-Hant/common/terms/form/1379?sort=id&amp;order=DESC&amp;equal_is_active=1" data-bs-toggle="tooltip" title="Edit" class="btn btn-primary"><i class="fa-solid fa-pencil"></i></a></td>
                </tr>
				                <tr>
					<td class="text-start">1377</td>
					<td class="text-start">EXP</td>
					<td class="text-start">費用</td>
					<td class="text-start">進貨單單別</td>
					<td class="text-start">                                  是
                                </td>
                    <td class="text-end"><a href="http://sysdev.chinabing.test/backend/zh-Hant/common/terms/form/1377?sort=id&amp;order=DESC&amp;equal_is_active=1" data-bs-toggle="tooltip" title="Edit" class="btn btn-primary"><i class="fa-solid fa-pencil"></i></a></td>
                </tr>
				            </tbody>
		</table>
	</div>
  <nav>
        <ul class="pagination">
            
                            <li class="page-item disabled" aria-disabled="true" aria-label="&laquo; Previous">
                    <span class="page-link" aria-hidden="true">&lsaquo;</span>
                </li>
            
            
                            
                
                
                                                                                        <li class="page-item active" aria-current="page"><span class="page-link">1</span></li>
                                                                                                <li class="page-item"><a class="page-link" href="http://sysdev.chinabing.test/backend/zh-Hant/common/terms/list?sort=id&amp;order=DESC&amp;equal_is_active=1&amp;page=2">2</a></li>
                                                                                                <li class="page-item"><a class="page-link" href="http://sysdev.chinabing.test/backend/zh-Hant/common/terms/list?sort=id&amp;order=DESC&amp;equal_is_active=1&amp;page=3">3</a></li>
                                                                                                <li class="page-item"><a class="page-link" href="http://sysdev.chinabing.test/backend/zh-Hant/common/terms/list?sort=id&amp;order=DESC&amp;equal_is_active=1&amp;page=4">4</a></li>
                                                                                                <li class="page-item"><a class="page-link" href="http://sysdev.chinabing.test/backend/zh-Hant/common/terms/list?sort=id&amp;order=DESC&amp;equal_is_active=1&amp;page=5">5</a></li>
                                                                                                <li class="page-item"><a class="page-link" href="http://sysdev.chinabing.test/backend/zh-Hant/common/terms/list?sort=id&amp;order=DESC&amp;equal_is_active=1&amp;page=6">6</a></li>
                                                                                                <li class="page-item"><a class="page-link" href="http://sysdev.chinabing.test/backend/zh-Hant/common/terms/list?sort=id&amp;order=DESC&amp;equal_is_active=1&amp;page=7">7</a></li>
                                                                                                <li class="page-item"><a class="page-link" href="http://sysdev.chinabing.test/backend/zh-Hant/common/terms/list?sort=id&amp;order=DESC&amp;equal_is_active=1&amp;page=8">8</a></li>
                                                                                                <li class="page-item"><a class="page-link" href="http://sysdev.chinabing.test/backend/zh-Hant/common/terms/list?sort=id&amp;order=DESC&amp;equal_is_active=1&amp;page=9">9</a></li>
                                                                        
            
                            <li class="page-item">
                    <a class="page-link" href="http://sysdev.chinabing.test/backend/zh-Hant/common/terms/list?sort=id&amp;order=DESC&amp;equal_is_active=1&amp;page=2" rel="next" aria-label="Next &raquo;">&rsaquo;</a>
                </li>
                    </ul>
    </nav>

</form></div>
        </div>
      </div>
    </div>
  </div>
</div>

      <footer id="footer"><a href="javascript:void(0)">中華一餅</a> &copy; 2009-2023 All Rights Reserved.</footer>

      <script type="text/javascript"><!--
$('#term').on('click', 'thead a, .pagination a', function(e) {
	e.preventDefault();

	$('#term').load(this.href);
});

$('#button-filter').on('click', function() {
	url = '?';

  var filter_taxonomy_name = $('#input-taxonomy_name').val();

  if (filter_taxonomy_name) {
    url += '&filter_taxonomy_name=' + encodeURIComponent(filter_taxonomy_name);
  }

  var filter_code = $('#input-code').val();

  if (filter_code) {
    url += '&filter_code=' + encodeURIComponent(filter_code);
  }

	var filter_name = $('#input-name').val();

	if (filter_name) {
		url += '&filter_name=' + encodeURIComponent(filter_name);
	}

  var equal_is_active = $('#input-equal_is_active').val();

  if (equal_is_active) {
    url += '&equal_is_active=' + encodeURIComponent(equal_is_active);
  }

	list_url = "http://sysdev.chinabing.test/backend/zh-Hant/common/terms/list" + url;

	$('#term').load(list_url);

  add_url = $("#button-add").attr("href") + url
  $("#button-add").attr("href", add_url);

});
//--></script>

    </div>

    <script src="http://sysdev.chinabing.test/backend/assets-admin/javascript/bootstrap/js/bootstrap.bundle.min.js" type="text/javascript"></script>

</body>
</html>
