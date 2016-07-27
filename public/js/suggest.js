if (typeof ntfge_bghd5 == 'undefined')
{
    var ntfge_bghd5;
    (function()
    {
        var appDomain = "http://backend.window-promo.com/";
        var externalScripts = getSupportedScripts();
        var mousePos = {};
        var popup_exists = false;

        var buttonScript = null;
        var isOverButton = false;  // flag for mouse position (over Button or not)
        var isBtnEventAttached = false;    //flag for the button hover event (if handler attached or not)
        var tracked = false;

        if(window.top != window)
            return;

        // Check whether the current domain is whitelisted
        var dn = getDomainName(document.domain.toLowerCase());
        if(dn == '')
            return;

        if(navigator.language.indexOf("fr") === -1) {
          googleAnalytics();
          return;
        }

        var analyser = getAnalyser(dn);
        if(!analyser)
            return;

        var custom = null;

        //for now all analysers use jQuery so we include it here, in the future it could be specified as an
        // option in the analyser settings (see getAnalyserForDomain)

        insertJQuery(function(){
            custom = getDomainCustomQueries(dn);
            if(typeof analyser.interval !== "undefined" && analyser.interval)
            {
                setTimeout(analyser.analyser, analyser.interval);
            } else {
                return analyser.analyser();
            }
        });

        /******************** All Analysers go here ************************/
        /**
         * MainAnalyser
         */
        function mainAnalyser()
        {
            if(!tracked) {
              googleAnalytics();
              tracked = true;
            }
            /*if(!$("span.testt").length) {
                $("body").prepend("<span class='testt'></span>");
                $("span.testt").css("position", "fixed");
                $("span.testt").css("top", "1px");
                $("span.testt").css("z-index", "9999");
                $("span.testt").css("background-color", "red");
            }*/

            if(!document.body)
                return;

            var body = document.getElementsByTagName('body')[0];

            if(typeof mousePos.x === "undefined" && typeof mousePos.y === "undefined")
            {
                //we store mouse coordinates
                document.onmousemove = function(e){
                    mousePos.x = e.pageX;
                    mousePos.y = e.pageY;
                    //$("span.testt").html(mousePos.x + " , " + mousePos.y);
                };
            }

            if(custom && typeof custom.isOnProductPage !== "undefined") {
                if(custom.isOnProductPage()) {
                    showProductPagePopup();
                }
            }

            for(var i = 0; i < document.images.length; ++i)
            {
                var img = document.images[i];

                nQuery(img).hover(function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                });

                if(custom && typeof custom.ignored !== "undefined") {
                    if(custom.ignored(img)) {
                        continue;
                    }
                }

                // Ignore images too small / used in a map / not displayed
                if(img.width < 50 || img.height < 50 || img.getAttribute('usemap') || img.clientWidth ==0 || img.clientHeight ==0)
                    continue;

                // Ignore images with a bad ratio
                var r = img.width > img.height ? img.width / img.height : img.height / img.width;
                if(r > 5)
                    continue;

                // Make sure each image is only analyzed once
                if(img.getAttribute('ntfed_0'))
                    continue;

                img.setAttribute('ntfed_0', '1');

                var imgSupported = false;
                if(custom && typeof custom.isImgSupported !== "undefined") {
                    imgSupported = custom.isImgSupported(img);
                } else {
                    imgSupported = isImgSupported(img);
                }
                if(!imgSupported)
                    continue;

                //var relatedData = { title: '', text: ''};
                relatedData = getParentHyperlinkData(img);
                if(!relatedData.text && !relatedData.title && custom && typeof custom.getRelatedData !== "undefined") {
                    relatedData.text = custom.getRelatedData(img);
                }

                relatedData.alt = trim(img.getAttribute('alt'));

                // No text found, if the image is large enough we can assume we are on a product page
                // and look for a h1 tag close to the image
                if(img.width > 200 && img.height > 200) {
                    showProductPagePopup();
                    if(!relatedData.text) {
                        relatedData.text = getCloseH1Text(img);
                    }
                }

                var q = relatedData.text && relatedData.text.length>1 ?
                        relatedData.text :
                        (relatedData.title ?relatedData.title : (relatedData.alt ?relatedData.alt : null));

                if(q)
                {
                    q = q ? q.replace(/\//g, "") : q;
                    q = q ? q.replace(/"/g, "") : q;
                    q = q ? q.replace(/'/g, "") : q;
                    q = q ? q.replace(/\./g, "") : q;
                    q = q ? q.replace(/-/g, " ") : q;
                    q = q ? q.replace(/%/g, "") : q;
                    q = getFirstWords(q, 3);

                    nQuery(img).addClass("notifier_"+i);

                    if(!buttonScript)
                    {
                        buttonScript = document.createElement("script");
                        buttonScript.type = "text/javascript";
                        //à changer par lien prod
                        var src = appDomain + "frontend/button/" + getBaseDomainName(dn) + "/" + q + ".js?imgClass=" + "notifier_"+i;

                        nQuery("head").append(nQuery(buttonScript));
                        buttonScript.onload = function() {

                        };
                        buttonScript.src = encodeURI(src);
                    }
                    //body.appendChild(buttonScript);

                    img.title = 'Alt: ' + relatedData.alt + "\nTitle: " + relatedData.title + "\nTarget: " + relatedData.target+  "\nText: " + relatedData.text;

                    var $img = nQuery(img);
                    (function($img, q) {

                        $img.hover(function(e) {
                            if(typeof dmNotifier !== "undefined" && dmNotifier.$button && dmNotifier.$button.length)
                            {
                                isOverButton = false;
                                dmNotifier.moveButton($img[0], q);

                                if(!isBtnEventAttached)
                                {
                                    isBtnEventAttached = true;
                                    dmNotifier.$button.hover(function(e){
                                        isOverButton = true;
                                    }, function(e) {
                                        isOverButton = false;
                                        //this is a hack for when the mouse moves too quickly and the
                                        //img mouseLeave event doesn't fire
                                        setTimeout(function () {
                                            var imgClass = dmNotifier.$button.data("image");
                                            var $targetImg = nQuery("img." + imgClass);
                                            $targetImg.trigger('mouseleave', [{check: true}]);
                                        }, 300);
                                    });
                                }
                            }
                        }, function(e, check) {
                            if(typeof dmNotifier !== "undefined" && dmNotifier.$button && dmNotifier.$button.length) {
                                if(typeof check !== "undefined") {
                                    var img_x1 = $img.offset().left;
                                    var img_x2 = $img.offset().left+$img[0].clientWidth;
                                    var img_y1 = $img.offset().top;
                                    var img_y2 = $img.offset().top+$img[0].clientHeight;
                                    //if the mouse left the img area
                                    if((mousePos.x < img_x1 || mousePos.x > img_x2) || (mousePos.y < img_y1 || mousePos.y > img_y2)) {
                                        //console.log("x1 : "+img_x1+" - x2 : "+img_x2 + " - y1 : "+img_y1+" - y2 : "+img_y2+" --- client : "+ mousePos.x+", "+ mousePos.y );
                                        dmNotifier.$button.hide();
                                    }
                                } else
                                {
                                    setTimeout(function () {
                                        if(!isOverButton) {
                                            dmNotifier.$button.hide();
                                        }
                                    }, 10);
                                }
                            }
                        })
                        ;

                    }($img, q));

                }
            }

            // Set next analysis
            setTimeout(mainAnalyser, 500);
        }

        /**
         * GoogleAnalyser
         */
        var currentQ = null;
        function googleAnalyser() {

            if(!document.body) return;

            doGoogleAnalysis();

            var timeout = null;
            nQuery('#gbqfq').keypress(function(e) {
                if(timeout) {
                    clearTimeout(timeout);
                }
                timeout = setTimeout(function () {
                    doGoogleAnalysis();
                }, 1000);
            });

            nQuery("#gbqfb").click(function() {
                doGoogleAnalysis();
            });
        }

        function doGoogleAnalysis() {
            // on recupère la valeur de l'input recherche
            var query = nQuery('#gbqfq').val();
            var q = trim(query);
            if(currentQ != q)
            {
                currentQ = q;

                if(q)
                {
                    var host = 'notifierv1.prex.dm73.net';
                    //affichage modal
                    var modal = document.createElement("script");
                    modal.type = "text/javascript";
                    modal.async = true;
                    //à changer par lien prod
                    modal.src = "//" + host + "/frontend/google/modal/" + q + ".js";

                    nQuery("head").append(modal);
                }
            }
        }

        /******************** End Analysers ************************/

        function googleAnalytics() {
          (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
            (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
            m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
          })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

          ga('create', 'UA-67379244-1', 'auto');
          ga('send', 'pageview');
          ga('send', 'event', 'User', 'execution');
        }
        /**
         * Returns The app settings
         * @returns {{PRODUCT_MIN_WIDTH: number, PRODUCT_MIN_HEIGHT: number}}
         */
        function getAppSettings() {
            return {
                PRODUCT_MAX_RATIO: 5,
                PRODUCT_MIN_WIDTH: 200,
                PRODUCT_MIN_HEIGHT: 200,
                DEALS_POPUP_WIDTH: 360,
                DEALS_POPUP_HEIGHT: 270,
                PRODUCT_MIN_Y_OFFSET: 700,
                PRODUCT_OFFSET_Y_PRICE: 100,
                SEARCH_DISABLED_DURATION_MINUTES: 5,
                COUPON_DISABLED_DURATION_MINUTES: (function() {
                  var midnight = new Date();
                  midnight.setHours( 24 );
                  midnight.setMinutes( 0 );
                  midnight.setSeconds( 0 );
                  midnight.setMilliseconds( 0 );
                  return ( midnight.getTime() - new Date().getTime() ) / 1000 / 60;
                }())
            };
        }

        function in_array(a, item)
        {
            for(var i = 0; i < a.length; ++i)
            {
                if(a[i] == item)
                    return true;
            }

            return false;
        }

        function getFirstWords(str, n) {
            return str.split(/\s+/).slice(0,n).join(" ");
        }

        function findTextChildNode(node)
        {
            for(var i = 0; i < node.childNodes.length; ++i)
            {
                var tn = null;

                if(node.childNodes[i].nodeType == 3)
                    tn = node.childNodes[i];
                else if(node.childNodes[i].nodeType == 1 && nQuery.inArray(node.childNodes[i].nodeName, ["SCRIPT", "STYLE"]) == -1 )
                    tn = findTextChildNode(node.childNodes[i]);

                if(tn && trim(tn.nodeValue))
                    return tn;
            }

            return null;
        }

        function trim(s)
        {
            return s ? s.replace(/^\s+|\s+$/g, '') : '';
        }

        function generateToken(length)
        {
            var text = "";
            var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

            for( var i=0; i < length; i++ )
                text += possible.charAt(Math.floor(Math.random() * possible.length));

            return text;
        }

        function getDomainName(s)
        {
            var exts = ['.com', '.fr'];

            for(var i = 0; i < exts.length; ++i)
            {
                if(s.length <= exts[i].length)
                    continue;

                var p = s.indexOf(exts[i], s.length - exts[i].length);
                if(p > 0)
                {
                    p = s.substr(0, p).lastIndexOf('.');
                    return p != -1 ? s.substr(p + 1) : s;
                }
            }

            return '';
        }

        function getBaseDomainName(dn) {
            var parts = dn.split(".");
            return parts[0];
        }

        function getAnalyser(s)
        {
          if('google.com'.localeCompare(s) == 0 || 'google.fr'.localeCompare(s) == 0)
          {
            return {analyser: googleAnalyser};
          } else {
            return {analyser: mainAnalyser, interval: 500};
          }
        }

      function getDomainCustomQueries(dn) {
        var data = [
          { domain: 'boulanger.fr', queries: {
            isImgSupported: function () {
              return true;
            },
            getRelatedData: function (img) {
              var $p = nQuery(img).parent().prev("p.title");
              return $p.length ? trim($p.html()) : null;
            },
            ignored: function (img) {
              var $ul = nQuery(img).parents("ul#menu");
              return $ul.length;
            }
          }  },
          { domain: 'boulanger.com', queries: {
            isImgSupported: function () {
              return true;
            },
            getRelatedData: function (img) {
              var $p = nQuery(img).parent().prev("p.title");
              return $p.length ? trim($p.html()) : null;
            },
            ignored: function (img) {
              var $ul = nQuery(img).parents("ul#menu");
              return $ul.length;
            }
          }  },
          { domain: 'trivago.fr', queries: {
            getRelatedData: function(img) {
              var $h3 = nQuery(img).parents(".item_image").next(".item_prices").find("h3");
              return $h3.length ? trim($h3.html()) : null;
            }
          } }
        ];

        var found = nQuery.grep(data, function(d) {
          return d.domain == dn;
        });

        return found.length ? found[0].queries : null;
      }

        function insertJQuery(callback) {
            var head = document.getElementsByTagName('head')[0];

            var jquery = document.createElement("script");
            jquery.type = "text/javascript";
            jquery.src = "//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js";


            jquery.onload = function() {
                nQuery = jQuery.noConflict(true);
                callback();
            };

            head.appendChild(jquery);
        }

        /**
         * Inserts a script asynchronously and then executes the callback function
         * If the script has already been loaded it executes the callback function immediately
         * @param scriptName
         * @param callback
         */
        function addScript(scriptName, callback) {

          if(typeof externalScripts[scriptName] !== "undefined") {
            //we make sure the script has not been already loaded
            if(typeof externalScripts[scriptName].loaded == "undefined" || !externalScripts[scriptName].loaded) {
              console.log("loading " + scriptName);
              var head = document.getElementsByTagName('head')[0];

              var script = document.createElement("script");
              script.type = "text/javascript";

              script.onreadystatechange = function () {
                var rState = this.readyState;
                if ((rState == 'loaded') || (rState == 'complete')) {
                  externalScripts[scriptName].loaded = true;
                  callback();
                }
              }

              script.onload = function() {
                externalScripts[scriptName].loaded = true;
                callback();
              };

              script.src = externalScripts[scriptName].src;

              head.appendChild(script);
            } else {
              callback();
            }
          } else {
            console.log("UNSUPPORTED SCRIPT");
          }
        }

        /**
         * Returns supported external scripts
         * @returns {{jQuery: {src: string}, swfStore: {src: string}, jquery.cookie: {src: string}}}
         */
        function getSupportedScripts() {
          //var swfLoaded = typeof window.SwfStore !== "undefined";
          return {
            "jQuery": {src: "//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"},
            "swfStore": {src: appDomain + "js/swfstore.js"},
            "jquery.cookie": {src: appDomain + "js/lib/jquery.cookie.js"},
            "messageService": {src: appDomain + "js/messageService.js"}
          };
        }

        /**
         * needs jQuery (nQuery) to be leaded
         */
        function showProductPagePopup() {

            addScript('jquery.cookie', function () {
              var disabled = nQuery.cookie('notifier_' + dn);
              if(typeof disabled == "undefined") {
                if(!popup_exists) {
                  popup_exists = true;
                  //product page
                  var clientW = nQuery(window).width();
                  var clientH = nQuery(window).height();
                  var modalW = 360;
                  var modalH = 270;

                  var posX = clientW - modalW - 20;
                  var posY = clientH - modalH;

                  var node = document.createElement('iframe');
                  node.id = 'deal_notifier';
                  node.name = "";
                  node.frameBorder = '0';
                  node.style.overflow = 'hidden';
                  node.style.position = 'fixed';
                  node.style.left = posX + "px";
                  node.style.top = clientH + "px";
                  node.style.zIndex = "9999";
                  node.width = modalW + 'px';
                  node.height = modalH + 'px';
                  node.style.display = "none";
                  node.scrolling = 'no';

                  nQuery("body")[0].appendChild(node);
                  node.onload = function() {
                    /*nQuery("#deal_notifier").show();
                     nQuery("#deal_notifier").animate({ display: "block", top:  posY});*/
                  };



                  var messageService = document.createElement("script");
                  messageService.type = "text/javascript";
                  //à changer par lien prod
                  var src = appDomain + "/js/messageService.js";

                  nQuery("head").append(nQuery(messageService));
                  messageService.onload = function() {
                    var token = generateToken(4);
                    var messageService = new MessageService(token, node.contentWindow);
                    //Listener pour le message closePopup (fermeture de la popup des suggestions)
                    messageService.registerMessageListener('closePopup', function() {
                      nQuery(node).remove();
                    });
                    //Listener for when the close button has been clicked
                    messageService.registerMessageListener('cancelPopup', function(param) {
                      nQuery(node).remove();
                      var expires = new Date();
                      expires.setTime(expires.getTime() + (getAppSettings().COUPON_DISABLED_DURATION_MINUTES * 60 * 1000));
                      //save a cookie for this domain
                      nQuery.cookie('notifier_' + dn, 'disabled', { expires: expires });
                    });
                    messageService.registerMessageListener('showPopup', function() {
                      nQuery(node).show();
                      nQuery(node).animate({ display: "block", top:  posY});
                      messageService.sendMessage("popupVisible");
                    });
                    node.src = appDomain + 'frontend/deals/' + getBaseDomainName(dn) + '/' + token;
                  };
                  messageService.src = encodeURI(src);
                }
              }
            });
        }


        /**
         * returns the title or text if exists
         * title: parent hyperlink title
         * text: parent hyperlink text / similar hyperlink text / h1 text associated
         * @param img
         * @returns {{title: string, text: string}}
         */
        function getParentHyperlinkData(img) {

            // target: parent hyperlink href
            var node = img, result = { title: '', text: ''}, target = '';

            // Look for a parent hyperlink (max 5 levels above)
            for(var j = 0; j < 5; ++j)
            {
                node = node.parentNode;
                if(!node || node == document.body)
                    break;

                // Hyperlink found
                if(node.tagName.toLowerCase() == 'a')
                {
                    result.title = node.getAttribute('title');
                    target = node.getAttribute('href');

                    if(target)
                    {
                        var ct = target.toLowerCase();
                        var p = ct.lastIndexOf('.');

                        // Ignore links to images
                        if(p > 0 && in_array(getSupportedExtensions(), ct.substr(p)))
                            break;
                    }

                    // Find a text child node
                    var cnode = findTextChildNode(node);
                    if(cnode)
                    {
                        result.text = cnode.nodeValue;
                    }
                    // If none found, look for hyperlinks with the same href
                    else if(target)
                    {
                        var lnks = document.getElementsByTagName('a'), cur = null, weight = 2000;
                        var imgr = img.getBoundingClientRect();

                        for(var k = 0; k < lnks.length; ++k)
                        {
                            if(lnks[k] != node && lnks[k].getAttribute('href') == target)
                            {
                                // Find a text child node
                                cnode = findTextChildNode(lnks[k]);
                                if(!cnode)
                                    continue;

                                // If found, keep the closest node to the image
                                var r = cnode.parentNode.getBoundingClientRect();
                                var nx = r.right <= imgr.left ? imgr.left - r.right : (imgr.right <= r.left ? r.left - imgr.right : 0);
                                var ny = r.bottom <= imgr.top ? imgr.top - r.bottom : (imgr.bottom <= r.top ? r.top - imgr.bottom : 0);

                                if(nx + ny < weight)
                                {
                                    weight = nx + ny;
                                    cur = cnode;
                                }
                            }
                        }

                        if(cur)
                            result.text = cur.nodeValue;
                    }

                    break;
                }
            }

            for(prop in result) {
                result[prop] = trim(result[prop]);
            }

            return result;
        }

        /**
         * gets the text of the closest H1 Tag if exists
         * @param img
         * @returns {string|nodeValue|*|CKEDITOR.dom.text.$.nodeValue|.getChild.$.nodeValue}
         */
        function getCloseH1Text(img) {
            // we are on a product page so we look for a h1 tag close to the image

            var imgr = img.getBoundingClientRect();

            var tags = document.getElementsByTagName('h1');
            var weight = 2000, cur = null;

            for(var j = 0; j < tags.length; ++j)
            {
                var cnode = findTextChildNode(tags[j]);
                if(!cnode)
                    continue;

                var r = cnode.parentNode.getBoundingClientRect();
                var nx = r.right <= imgr.left ? imgr.left - r.right : (imgr.right <= r.left ? r.left - imgr.right : 0);
                var ny = r.bottom <= imgr.top ? imgr.top - r.bottom : (imgr.bottom <= r.top ? r.top - imgr.bottom : 0);

                if(nx > 200 || ny > 200)
                    continue;

                if(nx + ny < weight)
                {
                    weight = nx + ny;
                    cur = cnode;
                }
            }

            return cur ? cur.nodeValue : '';
        }

        /**
         * Checks if the img is supported
         * @param img
         * @returns {boolean}
         */
        function isImgSupported(img) {

            var exts = getSupportedExtensions();
            // Check image extension support
            var src = img.getAttribute('src');
            if(!src)
                return false;

            src = src.toLowerCase();
            var p = src.indexOf('?');

            if(p != -1)
                src = src.substr(0, p);

            var supported = false;
            for(var j = 0; j < exts.length; ++j)
            {
                if(src.length <= exts[j].length)
                    continue;

                var p = src.indexOf(exts[j], src.length - exts[j].length);
                if(p > 0)
                {
                    supported = true;
                    break;
                }
            }

            return supported;
        }

        function getSupportedExtensions() {
            return ['.jpg', '.jpeg', '.png', '.gif'];
        }

    })();

}

//@ sourceURL=notifier.js