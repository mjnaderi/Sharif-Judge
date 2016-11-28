/*
 * Snippet :: jQuery Syntax Highlighter v2.0.0
 * http://steamdev.com/snippet
 *
 * Copyright 2011, SteamDev
 * Released under the MIT license.
 * http://www.opensource.org/licenses/mit-license.php
 *
 * Date: Wed Jan 19, 2011
 *
 * This file has been changed by Mohammad Javad Naderi <mjnaderi@gmail.com>
 *     - removed zeroclipboard
 *     - removed unnecessary languages
 *     - added select all
 */

(function($) {
	
	//enables console.log() in all browsers for error messages
	window.log=function(){log.history=log.history||[];log.history.push(arguments);if(this.console){console.log(Array.prototype.slice.call(arguments))}};		  

	$.fn.snippet = function(language,settings) {
	
		if(typeof language == "object"){settings = language;}
		
		if(typeof language == "string"){
			language = language.toLowerCase();
		}
		
		var defaults = {
			style:"random",
			showNum:true,
			transparent:false,
			collapse:false,
			menu:true,
			showMsg:"Expand Code",
			hideMsg:"Collapse Code",
			clipboard:"",
			startCollapsed:true,
			startText:false,
			box:"",
			boxColor:"",
			boxFill:""
		};
		
		// array containing all style names
		var styleArr = ["acid","berries-dark","berries-light","bipolar","blacknblue","bright","contrast","darkblue","darkness","desert","dull","easter","emacs","golden","greenlcd","ide-anjuta","ide-codewarrior","ide-devcpp","ide-eclipse","ide-kdev","ide-msvcpp","kwrite","matlab","navy","nedit","neon","night","pablo","peachpuff","print","rand01","the","typical","vampire","vim","vim-dark","whatis","whitengrey","zellner"];
		
		if(settings){$.extend(defaults,settings)}

		return this.each(function() {

			// variable containing the style to be used
			var useStyle = defaults.style.toLowerCase();
			if(defaults.style == "random"){
				var randomnumber=Math.floor(Math.random()*(styleArr.length));		   
				useStyle = styleArr[randomnumber];	
			}

			// variable containing the selected node
			var o = $(this);
			
			// the name of the selected node
			var node = this.nodeName.toLowerCase();
			
			// if the node is indeed a <pre> element...
			if(node == "pre"){
				
				// saves the original html as a data on the node
				if(o.data('orgHtml')==undefined || o.data('orgHtml')==null){
					var orgHtml = o.html();
					o.data('orgHtml',orgHtml);
				}
				
				// if node IS NOT an existing Snippet...
				if(!o.parent().hasClass("snippet-wrap")){
					
					// if language is NOT a string...
					if(typeof language != "string"){
						if(o.attr('class').length>0){var errclass=" class=\""+o.attr('class')+"\""}else{var errclass="";}
						if(o.attr('id').length>0){var errid=" id=\""+o.attr('id')+"\""}else{var errid="";}
						var error = "Snippet Error: You must specify a language on inital usage of Snippet. Reference <pre"+errclass+errid+">";
						console.log(error);
						return false;
					}
					
					o.addClass("sh_"+language).addClass("snippet-formatted").wrap("<div class='snippet-container' style='"+o.attr('style')+";'><div class='sh_"+useStyle+" snippet-wrap'></div></div>");
					o.removeAttr('style');
					sh_highlightDocument();
					
					// build an ordered list if showNum is true
					if(defaults.showNum){
						var newhtml = o.html();
						newhtml=newhtml.replace(/\n/g, "</li><li>");
						newhtml="<ol class='snippet-num'><li>"+newhtml+"</li></ol>";
						while(newhtml.indexOf("<li></li></ol>") != -1){
							newhtml=newhtml.replace("<li></li></ol>","</ol>");
						}		
					} 
					// build an unordered list if showNum is false
					else {
						var newhtml = o.html();
						newhtml=newhtml.replace(/\n/g, "</li><li>");
						newhtml="<ul class='snippet-no-num'><li>"+newhtml+"</li></ul>";
						while(newhtml.indexOf("<li></li></ul>") != -1){
							newhtml=newhtml.replace("<li></li></ul>","</ul>");
						}
					}
					
					// normalizes tab space by replacing them with 4 non-breaking spaces
					newhtml=newhtml.replace(/\t/g, "&nbsp;&nbsp;&nbsp;&nbsp;");	
											
					
					// insert highlighted code into <pre> element
					o.html(newhtml);					
					
					// cleans up the highlighted html
					while(o.find("li").eq(0).html() == ""){
						o.find("li").eq(0).remove();
					}
					o.find("li").each(function(){
						if($(this).html().length<2){
							var rep = ($(this).html()).replace(/\s/g,"");
							if(rep==""){
								/*if($.browser.opera){*/
									$(this).html("&nbsp;");
								/*} else {
									$(this).html("<span style='display:none;'>&nbsp;</span>");	
								}*/
							}
						}
					});
					
					// builds text-only view and hover menu
					var txtOnly = "<pre class='snippet-textonly sh_sourceCode' style='display:none;'>"+o.data('orgHtml')+"</pre>";
					var controls = "<div class='snippet-menu sh_sourceCode' style='display:none;'><pre>"
								  +"<a class='snippet-copy' href='#'>copy</a>"
								  +"<a class='snippet-select' href='#'>select all</a>"
								  +"<a class='snippet-text' href='#'>text</a>"
								  +"<a class='snippet-window' href='#'>pop-up</a>"
								  +"</pre></div>";
								  
					o.parent().append(txtOnly);	  
					o.parent().prepend(controls);
					o.parent().hover(function(){$(this).find('.snippet-menu').fadeIn("fast");},function(){$(this).find('.snippet-menu').fadeOut("fast");});
					
					// builds clipboard
					if(defaults.clipboard!="" && defaults.clipboard!=false){
						var cpy = o.parent().find('a.snippet-copy');
						cpy.show();
						cpy.parents('.snippet-menu').show();
						var txt = o.parents('.snippet-wrap').find('.snippet-textonly').text();
						ZeroClipboard.setMoviePath(defaults.clipboard);
						var clip = new ZeroClipboard.Client();
						clip.setText(txt);
						clip.glue(cpy[0], cpy.parents('.snippet-menu')[0]);
						clip.addEventListener( 'complete', function(client, text) {
							if(text.length > 500){
								text = text.substr(0,500)+"...\n\n("+(text.length-500)+" characters not shown)";	
							}
							alert("Copied text to clipboard:\n\n " + text );
						});

						cpy.parents('.snippet-menu').hide();

					} else {
						o.parent().find('a.snippet-copy').hide();	
					}
					
					// click event for text-only view
					o.parent().find("a.snippet-text").click(function(){
						var org = $(this).parents('.snippet-wrap').find('.snippet-formatted');
						var txt = $(this).parents('.snippet-wrap').find('.snippet-textonly');
						org.toggle();
						txt.toggle();
						
						if(txt.is(':visible')){
							$(this).html("html");
						} else {
							$(this).html("text");
						}
						$(this).blur();
						return false;
					});

					// click event for select all (feature added by mjnaderi)
					o.parent().find("a.snippet-select").click(function(){
						$('pre.sh_sourceCode:visible').selectText();
						return false;
					});
					
					// click event for popup view
					o.parent().find("a.snippet-window").click(function(){
						var txt = $(this).parents('.snippet-wrap').find('.snippet-textonly').html();
						snippetPopup(txt);
						$(this).blur();
						return false;
					});						
					
					// disables menu
					if(!defaults.menu){
						o.prev('.snippet-menu').find('pre,.snippet-clipboard').hide();
					}
					
					// collapse functionality
					if(defaults.collapse){
						var styleClass = o.parent().attr('class');
						var collapseShow = "<div class='snippet-reveal "+styleClass+"'><pre class='sh_sourceCode'><a href='#' class='snippet-toggle'>"+defaults.showMsg+"</a></pre></div>";
						var collapseHide = "<div class='sh_sourceCode snippet-hide'><pre><a href='#' class='snippet-revealed snippet-toggle'>"+defaults.hideMsg+"</a></pre></div>";
						
						o.parents('.snippet-container').append(collapseShow);
						o.parent().append(collapseHide);
						
						var root = o.parents('.snippet-container');
						if(defaults.startCollapsed){
							root.find('.snippet-reveal').show();
							root.find('.snippet-wrap').eq(0).hide();
						} else {
							root.find('.snippet-reveal').hide();
							root.find('.snippet-wrap').eq(0).show();
						}
						
						root.find('a.snippet-toggle').click(function(){
							root.find('.snippet-wrap').toggle();
							return false;
						});
						
					}
					
					// makes snippet background transparent
					if(defaults.transparent){
						var styleObj = {"background-color":"transparent","box-shadow":"none","-moz-box-shadow":"none","-webkit-box-shadow":"none"} 
						o.css(styleObj);
						o.next(".snippet-textonly").css(styleObj);	
						o.parents('.snippet-container').find('.snippet-reveal pre').css(styleObj);
					}
					
					// starts snippet on text-only view
					if(defaults.startText){
						o.hide();
						o.next(".snippet-textonly").show();
						o.parent().find(".snippet-text").html("html");
						
					}
					
					// boxes in specified lines of code
					if(defaults.box!=""){
						var spacer = "<span class='box-sp'>&nbsp;</span>";
						var boxNums = defaults.box.split(',');
						for(var i=0;i<boxNums.length;i++){
							var boxNum = boxNums[i];
								if(boxNum.indexOf('-')==-1){
									boxNum = parseFloat(boxNum)-1;
									o.find("li").eq(boxNum).addClass('box').prepend(spacer);
								} else {
									var numStart = parseFloat(boxNum.split('-')[0])-1;
									var numEnd = parseFloat(boxNum.split('-')[1])-1;
									if(numStart<numEnd){
										o.find("li").eq(numStart).addClass('box box-top').prepend(spacer);
										o.find("li").eq(numEnd).addClass('box box-bot').prepend(spacer);
										for(var x=numStart+1; x<numEnd; x++){
											o.find("li").eq(x).addClass('box box-mid').prepend(spacer);
										}
									} else if (numStart==numEnd){
										o.find("li").eq(numStart).addClass('box').prepend(spacer);
									}
								}
							
						}
						
						// sets the color of the box
						if(defaults.boxColor!=""){
							o.find("li.box").css('border-color',defaults.boxColor);	
						}
						
						// sets the fill (background color) of the box
						if(defaults.boxFill!=""){
							o.find("li.box, li.box-top, li.box-mid, li.box-bot").addClass('box-bg').css('background-color',defaults.boxFill);
						}	
						
						if($.browser.webkit){
							o.find(".snippet-num li.box").css('margin-left','-3.3em');
							o.find(".snippet-num li .box-sp").css('width','21px');	
						}
						
					}					
					
					// adds a css class to all links in the snippet so they are themed properly
					o.parents('.snippet-container').find("a").addClass("sh_url");
					
				}
				// if node IS an existing Snippet...
				else {
					
					// set new style classes, remove boxes
					o.parent().attr("class","sh_"+useStyle+" snippet-wrap");
					o.parents('.snippet-container').find('.snippet-reveal').attr("class","sh_"+useStyle+" snippet-wrap snippet-reveal");
					o.find("li.box, li.box-top, li.box-mid, li.box-bot").removeAttr('style').removeAttr('class');
					o.find("li .box-sp").remove();
					
					// set background to transparent
					if(defaults.transparent){
						var styleObj = {"background-color":"transparent","box-shadow":"none","-moz-box-shadow":"none","-webkit-box-shadow":"none"} 
						o.css(styleObj);
						o.next(".snippet-textonly").css(styleObj);	
						o.parents('.snippet-container').find('.snippet-hide pre').css(styleObj);
					}
					// remove transparency 
					else {
						var styleObj = {"background-color":"","box-shadow":"","-moz-box-shadow":"","-webkit-box-shadow":""} 
						o.css(styleObj);
						o.next(".snippet-textonly").css(styleObj);	
						o.parents('.snippet-container').find('.snippet-reveal pre').css(styleObj);						
					}
					
					// show numbers by switching <ul> to <ol>
					if(defaults.showNum){
			
						var list = o.find("li").eq(0).parent();
						if(list.hasClass("snippet-no-num")){
							list.wrap("<ol class='snippet-num'></ol>");
							var li = o.find("li").eq(0);
							li.unwrap();
						}
					} 
					// hide numbers by switching <ol> to <ul>
					else {
						var list = o.find("li").eq(0).parent();
						if(list.hasClass("snippet-num")){
							list.wrap("<ul class='snippet-no-num'></ul>");
							var li = o.find("li").eq(0);
							li.unwrap();
						}
					}
					
					// box in specified lines			
					if(defaults.box!=""){
						var spacer = "<span class='box-sp'>&nbsp;</span>";
						var boxNums = defaults.box.split(',');
						for(var i=0;i<boxNums.length;i++){
							var boxNum = boxNums[i];
								if(boxNum.indexOf('-')==-1){
									boxNum = parseFloat(boxNum)-1;
									o.find("li").eq(boxNum).addClass('box').prepend(spacer);
								} else {
									var numStart = parseFloat(boxNum.split('-')[0])-1;
									var numEnd = parseFloat(boxNum.split('-')[1])-1;
									if(numStart<numEnd){
										o.find("li").eq(numStart).addClass('box box-top').prepend(spacer);
										o.find("li").eq(numEnd).addClass('box box-bot').prepend(spacer);
										for(var x=numStart+1; x<numEnd; x++){
											o.find("li").eq(x).addClass('box box-mid').prepend(spacer);
										}
									} else if (numStart==numEnd){
										o.find("li").eq(numStart).addClass('box').prepend(spacer);
									}
								}
							
						}
						
						if(defaults.boxColor!=""){
							o.find("li.box").css('border-color',defaults.boxColor);	
						}
						
						if(defaults.boxFill!=""){
							o.find("li.box").addClass('box-bg').css('background-color',defaults.boxFill);
						}					
						
						if($.browser.webkit){
							o.find(".snippet-num li.box").css('margin-left','-3.3em');
							o.find(".snippet-num li .box-sp").css('width','21px');
						}						
						
					}
					

					
					sh_highlightDocument();
					
					// show/hide hover menu
					if(!defaults.menu){
						o.prev('.snippet-menu').find('pre,.snippet-clipboard').hide();					
					} else {
						o.prev('.snippet-menu').find('pre,.snippet-clipboard').show();	
					}
			
				}

			} else {
				var error = "Snippet Error: Sorry, Snippet only formats '<pre>' elements. '<"+node+">' elements are currently unsupported.";
				console.log(error);
				return false;
			}

		});

	};

})(jQuery);


// snippet new window popup function
function snippetPopup(content) {
	 top.consoleRef=window.open('','myconsole',
	  'width=600,height=300'
	   +',left=50,top=50'
	   +',menubar=0'
	   +',toolbar=0'
	   +',location=0'
	   +',status=0'
	   +',scrollbars=1'
	   +',resizable=1');
	 top.consoleRef.document.writeln(
	  '<html><head><title>Snippet :: Code View :: '+location.href+'</title></head>'
	   +'<body bgcolor=white onLoad="self.focus()">'
	   +'<pre>'+content+'</pre>'
	   +'</body></html>'
	 );
	 top.consoleRef.document.close();
}


/* SHJS */
/* Copyright (C) 2007, 2008 gnombat@users.sourceforge.net */
/* License: http://shjs.sourceforge.net/doc/gplv3.html */

if(!this.sh_languages){this.sh_languages={}}var sh_requests={};function sh_isEmailAddress(a){if(/^mailto:/.test(a)){return false}return a.indexOf("@")!==-1}function sh_setHref(b,c,d){var a=d.substring(b[c-2].pos,b[c-1].pos);if(a.length>=2&&a.charAt(0)==="<"&&a.charAt(a.length-1)===">"){a=a.substr(1,a.length-2)}if(sh_isEmailAddress(a)){a="mailto:"+a}b[c-2].node.href=a}function sh_konquerorExec(b){var a=[""];a.index=b.length;a.input=b;return a}function sh_highlightString(B,o){if(/Konqueror/.test(navigator.userAgent)){if(!o.konquered){for(var F=0;F<o.length;F++){for(var H=0;H<o[F].length;H++){var G=o[F][H][0];if(G.source==="$"){G.exec=sh_konquerorExec}}}o.konquered=true}}var N=document.createElement("a");var q=document.createElement("span");var A=[];var j=0;var n=[];var C=0;var k=null;var x=function(i,a){var p=i.length;if(p===0){return}if(!a){var Q=n.length;if(Q!==0){var r=n[Q-1];if(!r[3]){a=r[1]}}}if(k!==a){if(k){A[j++]={pos:C};if(k==="sh_url"){sh_setHref(A,j,B)}}if(a){var P;if(a==="sh_url"){P=N.cloneNode(false)}else{P=q.cloneNode(false)}P.className=a;A[j++]={node:P,pos:C}}}C+=p;k=a};var t=/\r\n|\r|\n/g;t.lastIndex=0;var d=B.length;while(C<d){var v=C;var l;var w;var h=t.exec(B);if(h===null){l=d;w=d}else{l=h.index;w=t.lastIndex}var g=B.substring(v,l);var M=[];for(;;){var I=C-v;var D;var y=n.length;if(y===0){D=0}else{D=n[y-1][2]}var O=o[D];var z=O.length;var m=M[D];if(!m){m=M[D]=[]}var E=null;var u=-1;for(var K=0;K<z;K++){var f;if(K<m.length&&(m[K]===null||I<=m[K].index)){f=m[K]}else{var c=O[K][0];c.lastIndex=I;f=c.exec(g);m[K]=f}if(f!==null&&(E===null||f.index<E.index)){E=f;u=K;if(f.index===I){break}}}if(E===null){x(g.substring(I),null);break}else{if(E.index>I){x(g.substring(I,E.index),null)}var e=O[u];var J=e[1];var b;if(J instanceof Array){for(var L=0;L<J.length;L++){b=E[L+1];x(b,J[L])}}else{b=E[0];x(b,J)}switch(e[2]){case -1:break;case -2:n.pop();break;case -3:n.length=0;break;default:n.push(e);break}}}if(k){A[j++]={pos:C};if(k==="sh_url"){sh_setHref(A,j,B)}k=null}C=w}return A}function sh_getClasses(d){var a=[];var b=d.className;if(b&&b.length>0){var e=b.split(" ");for(var c=0;c<e.length;c++){if(e[c].length>0){a.push(e[c])}}}return a}function sh_addClass(c,a){var d=sh_getClasses(c);for(var b=0;b<d.length;b++){if(a.toLowerCase()===d[b].toLowerCase()){return}}d.push(a);c.className=d.join(" ")}function sh_extractTagsFromNodeList(c,a){var f=c.length;for(var d=0;d<f;d++){var e=c.item(d);switch(e.nodeType){case 1:if(e.nodeName.toLowerCase()==="br"){var b;if(/MSIE/.test(navigator.userAgent)){b="\r"}else{b="\n"}a.text.push(b);a.pos++}else{a.tags.push({node:e.cloneNode(false),pos:a.pos});sh_extractTagsFromNodeList(e.childNodes,a);a.tags.push({pos:a.pos})}break;case 3:case 4:a.text.push(e.data);a.pos+=e.length;break}}}function sh_extractTags(c,b){var a={};a.text=[];a.tags=b;a.pos=0;sh_extractTagsFromNodeList(c.childNodes,a);return a.text.join("")}function sh_mergeTags(d,f){var a=d.length;if(a===0){return f}var c=f.length;if(c===0){return d}var i=[];var e=0;var b=0;while(e<a&&b<c){var h=d[e];var g=f[b];if(h.pos<=g.pos){i.push(h);e++}else{i.push(g);if(f[b+1].pos<=h.pos){b++;i.push(f[b]);b++}else{i.push({pos:h.pos});f[b]={node:g.node.cloneNode(false),pos:h.pos}}}}while(e<a){i.push(d[e]);e++}while(b<c){i.push(f[b]);b++}return i}function sh_insertTags(k,h){var g=document;var l=document.createDocumentFragment();var e=0;var d=k.length;var b=0;var j=h.length;var c=l;while(b<j||e<d){var i;var a;if(e<d){i=k[e];a=i.pos}else{a=j}if(a<=b){if(i.node){var f=i.node;c.appendChild(f);c=f}else{c=c.parentNode}e++}else{c.appendChild(g.createTextNode(h.substring(b,a)));b=a}}return l}function sh_highlightElement(d,g){sh_addClass(d,"sh_sourceCode");var c=[];var e=sh_extractTags(d,c);var f=sh_highlightString(e,g);var b=sh_mergeTags(c,f);var a=sh_insertTags(b,e);while(d.hasChildNodes()){d.removeChild(d.firstChild)}d.appendChild(a)}function sh_getXMLHttpRequest(){if(window.ActiveXObject){return new ActiveXObject("Msxml2.XMLHTTP")}else{if(window.XMLHttpRequest){return new XMLHttpRequest()}}throw"No XMLHttpRequest implementation available"}function sh_load(language,element,prefix,suffix){if(language in sh_requests){sh_requests[language].push(element);return}sh_requests[language]=[element];var request=sh_getXMLHttpRequest();var url=prefix+"sh_"+language+suffix;request.open("GET",url,true);request.onreadystatechange=function(){if(request.readyState===4){try{if(!request.status||request.status===200){eval(request.responseText);var elements=sh_requests[language];for(var i=0;i<elements.length;i++){sh_highlightElement(elements[i],sh_languages[language])}}else{throw"HTTP error: status "+request.status}}finally{request=null}}};request.send(null)}


function sh_highlightDocument(prefix, suffix) {
	var nodeList = document.getElementsByTagName('pre');
	for (var i = 0; i < nodeList.length; i++) {
		var element = nodeList.item(i);
		var htmlClasses = element.className.toLowerCase();
		var htmlClass = htmlClasses.replace(/sh_sourcecode/g,'');
		if(htmlClass.indexOf("sh_")!=-1){htmlClass=htmlClass.match(/(\bsh_)\w+\b/g)[0];}
		if (htmlClasses.indexOf('sh_sourcecode') != -1) {continue;}
		if (htmlClass.substr(0, 3) === 'sh_') {
			var language = htmlClass.substring(3);
			if (language in sh_languages) {
				sh_highlightElement(element, sh_languages[language]);
			} else if (typeof(prefix) === 'string' && typeof(suffix) === 'string') {
				sh_load(language, element, prefix, suffix);
			} else {
				console.log('Found <pre> element with class="' + htmlClass + '", but no such language exists');
				continue;
			}
			break;
		}
	}
}

/* C language (http://shjs.sourceforge.net/lang/sh_c.min.js) */
if(!this.sh_languages){this.sh_languages={}}sh_languages.c=[[[/\/\/\//g,"sh_comment",1],[/\/\//g,"sh_comment",7],[/\/\*\*/g,"sh_comment",8],[/\/\*/g,"sh_comment",9],[/(\bstruct)([ \t]+)([A-Za-z0-9_]+)/g,["sh_keyword","sh_normal","sh_classname"],-1],[/^[ \t]*#(?:[ \t]*include)/g,"sh_preproc",10,1],[/^[ \t]*#(?:[ \t]*[A-Za-z0-9_]*)/g,"sh_preproc",-1],[/\b[+-]?(?:(?:0x[A-Fa-f0-9]+)|(?:(?:[\d]*\.)?[\d]+(?:[eE][+-]?[\d]+)?))u?(?:(?:int(?:8|16|32|64))|L)?\b/g,"sh_number",-1],[/"/g,"sh_string",13],[/'/g,"sh_string",14],[/\b(?:__asm|__cdecl|__declspec|__export|__far16|__fastcall|__fortran|__import|__pascal|__rtti|__stdcall|_asm|_cdecl|__except|_export|_far16|_fastcall|__finally|_fortran|_import|_pascal|_stdcall|__thread|__try|asm|auto|break|case|catch|cdecl|const|continue|default|do|else|enum|extern|for|goto|if|pascal|register|return|sizeof|static|struct|switch|typedef|union|volatile|while)\b/g,"sh_keyword",-1],[/\b(?:bool|char|double|float|int|long|short|signed|unsigned|void|wchar_t)\b/g,"sh_type",-1],[/~|!|%|\^|\*|\(|\)|-|\+|=|\[|\]|\\|:|;|,|\.|\/|\?|&|<|>|\|/g,"sh_symbol",-1],[/\{|\}/g,"sh_cbracket",-1],[/(?:[A-Za-z]|_)[A-Za-z0-9_]*(?=[ \t]*\()/g,"sh_function",-1],[/([A-Za-z](?:[^`~!@#$%&*()_=+{}|;:",<.>\/?'\\[\]\^\-\s]|[_])*)((?:<.*>)?)(\s+(?=[*&]*[A-Za-z][^`~!@#$%&*()_=+{}|;:",<.>\/?'\\[\]\^\-\s]*\s*[`~!@#$%&*()_=+{}|;:",<.>\/?'\\[\]\^\-\[\]]+))/g,["sh_usertype","sh_usertype","sh_normal"],-1]],[[/$/g,null,-2],[/(?:<?)[A-Za-z0-9_\.\/\-_~]+@[A-Za-z0-9_\.\/\-_~]+(?:>?)|(?:<?)[A-Za-z0-9_]+:\/\/[A-Za-z0-9_\.\/\-_~]+(?:>?)/g,"sh_url",-1],[/<\?xml/g,"sh_preproc",2,1],[/<!DOCTYPE/g,"sh_preproc",4,1],[/<!--/g,"sh_comment",5],[/<(?:\/)?[A-Za-z](?:[A-Za-z0-9_:.-]*)(?:\/)?>/g,"sh_keyword",-1],[/<(?:\/)?[A-Za-z](?:[A-Za-z0-9_:.-]*)/g,"sh_keyword",6,1],[/&(?:[A-Za-z0-9]+);/g,"sh_preproc",-1],[/<(?:\/)?[A-Za-z][A-Za-z0-9]*(?:\/)?>/g,"sh_keyword",-1],[/<(?:\/)?[A-Za-z][A-Za-z0-9]*/g,"sh_keyword",6,1],[/@[A-Za-z]+/g,"sh_type",-1],[/(?:TODO|FIXME|BUG)(?:[:]?)/g,"sh_todo",-1]],[[/\?>/g,"sh_preproc",-2],[/([^=" \t>]+)([ \t]*)(=?)/g,["sh_type","sh_normal","sh_symbol"],-1],[/"/g,"sh_string",3]],[[/\\(?:\\|")/g,null,-1],[/"/g,"sh_string",-2]],[[/>/g,"sh_preproc",-2],[/([^=" \t>]+)([ \t]*)(=?)/g,["sh_type","sh_normal","sh_symbol"],-1],[/"/g,"sh_string",3]],[[/-->/g,"sh_comment",-2],[/<!--/g,"sh_comment",5]],[[/(?:\/)?>/g,"sh_keyword",-2],[/([^=" \t>]+)([ \t]*)(=?)/g,["sh_type","sh_normal","sh_symbol"],-1],[/"/g,"sh_string",3]],[[/$/g,null,-2]],[[/\*\//g,"sh_comment",-2],[/(?:<?)[A-Za-z0-9_\.\/\-_~]+@[A-Za-z0-9_\.\/\-_~]+(?:>?)|(?:<?)[A-Za-z0-9_]+:\/\/[A-Za-z0-9_\.\/\-_~]+(?:>?)/g,"sh_url",-1],[/<\?xml/g,"sh_preproc",2,1],[/<!DOCTYPE/g,"sh_preproc",4,1],[/<!--/g,"sh_comment",5],[/<(?:\/)?[A-Za-z](?:[A-Za-z0-9_:.-]*)(?:\/)?>/g,"sh_keyword",-1],[/<(?:\/)?[A-Za-z](?:[A-Za-z0-9_:.-]*)/g,"sh_keyword",6,1],[/&(?:[A-Za-z0-9]+);/g,"sh_preproc",-1],[/<(?:\/)?[A-Za-z][A-Za-z0-9]*(?:\/)?>/g,"sh_keyword",-1],[/<(?:\/)?[A-Za-z][A-Za-z0-9]*/g,"sh_keyword",6,1],[/@[A-Za-z]+/g,"sh_type",-1],[/(?:TODO|FIXME|BUG)(?:[:]?)/g,"sh_todo",-1]],[[/\*\//g,"sh_comment",-2],[/(?:<?)[A-Za-z0-9_\.\/\-_~]+@[A-Za-z0-9_\.\/\-_~]+(?:>?)|(?:<?)[A-Za-z0-9_]+:\/\/[A-Za-z0-9_\.\/\-_~]+(?:>?)/g,"sh_url",-1],[/(?:TODO|FIXME|BUG)(?:[:]?)/g,"sh_todo",-1]],[[/$/g,null,-2],[/</g,"sh_string",11],[/"/g,"sh_string",12],[/\/\/\//g,"sh_comment",1],[/\/\//g,"sh_comment",7],[/\/\*\*/g,"sh_comment",8],[/\/\*/g,"sh_comment",9]],[[/$/g,null,-2],[/>/g,"sh_string",-2]],[[/$/g,null,-2],[/\\(?:\\|")/g,null,-1],[/"/g,"sh_string",-2]],[[/"/g,"sh_string",-2],[/\\./g,"sh_specialchar",-1]],[[/'/g,"sh_string",-2],[/\\./g,"sh_specialchar",-1]]];

/* C++ (cpp) language (http://shjs.sourceforge.net/lang/sh_cpp.min.js) */
if(!this.sh_languages){this.sh_languages={}}sh_languages.cpp=[[[/(\b(?:class|struct|typename))([ \t]+)([A-Za-z0-9_]+)/g,["sh_keyword","sh_normal","sh_classname"],-1],[/\b(?:class|const_cast|delete|dynamic_cast|explicit|false|friend|inline|mutable|namespace|new|operator|private|protected|public|reinterpret_cast|static_cast|template|this|throw|true|try|typeid|typename|using|virtual)\b/g,"sh_keyword",-1],[/\/\/\//g,"sh_comment",1],[/\/\//g,"sh_comment",7],[/\/\*\*/g,"sh_comment",8],[/\/\*/g,"sh_comment",9],[/(\bstruct)([ \t]+)([A-Za-z0-9_]+)/g,["sh_keyword","sh_normal","sh_classname"],-1],[/^[ \t]*#(?:[ \t]*include)/g,"sh_preproc",10,1],[/^[ \t]*#(?:[ \t]*[A-Za-z0-9_]*)/g,"sh_preproc",-1],[/\b[+-]?(?:(?:0x[A-Fa-f0-9]+)|(?:(?:[\d]*\.)?[\d]+(?:[eE][+-]?[\d]+)?))u?(?:(?:int(?:8|16|32|64))|L)?\b/g,"sh_number",-1],[/"/g,"sh_string",13],[/'/g,"sh_string",14],[/\b(?:__asm|__cdecl|__declspec|__export|__far16|__fastcall|__fortran|__import|__pascal|__rtti|__stdcall|_asm|_cdecl|__except|_export|_far16|_fastcall|__finally|_fortran|_import|_pascal|_stdcall|__thread|__try|asm|auto|break|case|catch|cdecl|const|continue|default|do|else|enum|extern|for|goto|if|pascal|register|return|sizeof|static|struct|switch|typedef|union|volatile|while)\b/g,"sh_keyword",-1],[/\b(?:bool|char|double|float|int|long|short|signed|unsigned|void|wchar_t)\b/g,"sh_type",-1],[/~|!|%|\^|\*|\(|\)|-|\+|=|\[|\]|\\|:|;|,|\.|\/|\?|&|<|>|\|/g,"sh_symbol",-1],[/\{|\}/g,"sh_cbracket",-1],[/(?:[A-Za-z]|_)[A-Za-z0-9_]*(?=[ \t]*\()/g,"sh_function",-1],[/([A-Za-z](?:[^`~!@#$%&*()_=+{}|;:",<.>\/?'\\[\]\^\-\s]|[_])*)((?:<.*>)?)(\s+(?=[*&]*[A-Za-z][^`~!@#$%&*()_=+{}|;:",<.>\/?'\\[\]\^\-\s]*\s*[`~!@#$%&*()_=+{}|;:",<.>\/?'\\[\]\^\-\[\]]+))/g,["sh_usertype","sh_usertype","sh_normal"],-1]],[[/$/g,null,-2],[/(?:<?)[A-Za-z0-9_\.\/\-_~]+@[A-Za-z0-9_\.\/\-_~]+(?:>?)|(?:<?)[A-Za-z0-9_]+:\/\/[A-Za-z0-9_\.\/\-_~]+(?:>?)/g,"sh_url",-1],[/<\?xml/g,"sh_preproc",2,1],[/<!DOCTYPE/g,"sh_preproc",4,1],[/<!--/g,"sh_comment",5],[/<(?:\/)?[A-Za-z](?:[A-Za-z0-9_:.-]*)(?:\/)?>/g,"sh_keyword",-1],[/<(?:\/)?[A-Za-z](?:[A-Za-z0-9_:.-]*)/g,"sh_keyword",6,1],[/&(?:[A-Za-z0-9]+);/g,"sh_preproc",-1],[/<(?:\/)?[A-Za-z][A-Za-z0-9]*(?:\/)?>/g,"sh_keyword",-1],[/<(?:\/)?[A-Za-z][A-Za-z0-9]*/g,"sh_keyword",6,1],[/@[A-Za-z]+/g,"sh_type",-1],[/(?:TODO|FIXME|BUG)(?:[:]?)/g,"sh_todo",-1]],[[/\?>/g,"sh_preproc",-2],[/([^=" \t>]+)([ \t]*)(=?)/g,["sh_type","sh_normal","sh_symbol"],-1],[/"/g,"sh_string",3]],[[/\\(?:\\|")/g,null,-1],[/"/g,"sh_string",-2]],[[/>/g,"sh_preproc",-2],[/([^=" \t>]+)([ \t]*)(=?)/g,["sh_type","sh_normal","sh_symbol"],-1],[/"/g,"sh_string",3]],[[/-->/g,"sh_comment",-2],[/<!--/g,"sh_comment",5]],[[/(?:\/)?>/g,"sh_keyword",-2],[/([^=" \t>]+)([ \t]*)(=?)/g,["sh_type","sh_normal","sh_symbol"],-1],[/"/g,"sh_string",3]],[[/$/g,null,-2]],[[/\*\//g,"sh_comment",-2],[/(?:<?)[A-Za-z0-9_\.\/\-_~]+@[A-Za-z0-9_\.\/\-_~]+(?:>?)|(?:<?)[A-Za-z0-9_]+:\/\/[A-Za-z0-9_\.\/\-_~]+(?:>?)/g,"sh_url",-1],[/<\?xml/g,"sh_preproc",2,1],[/<!DOCTYPE/g,"sh_preproc",4,1],[/<!--/g,"sh_comment",5],[/<(?:\/)?[A-Za-z](?:[A-Za-z0-9_:.-]*)(?:\/)?>/g,"sh_keyword",-1],[/<(?:\/)?[A-Za-z](?:[A-Za-z0-9_:.-]*)/g,"sh_keyword",6,1],[/&(?:[A-Za-z0-9]+);/g,"sh_preproc",-1],[/<(?:\/)?[A-Za-z][A-Za-z0-9]*(?:\/)?>/g,"sh_keyword",-1],[/<(?:\/)?[A-Za-z][A-Za-z0-9]*/g,"sh_keyword",6,1],[/@[A-Za-z]+/g,"sh_type",-1],[/(?:TODO|FIXME|BUG)(?:[:]?)/g,"sh_todo",-1]],[[/\*\//g,"sh_comment",-2],[/(?:<?)[A-Za-z0-9_\.\/\-_~]+@[A-Za-z0-9_\.\/\-_~]+(?:>?)|(?:<?)[A-Za-z0-9_]+:\/\/[A-Za-z0-9_\.\/\-_~]+(?:>?)/g,"sh_url",-1],[/(?:TODO|FIXME|BUG)(?:[:]?)/g,"sh_todo",-1]],[[/$/g,null,-2],[/</g,"sh_string",11],[/"/g,"sh_string",12],[/\/\/\//g,"sh_comment",1],[/\/\//g,"sh_comment",7],[/\/\*\*/g,"sh_comment",8],[/\/\*/g,"sh_comment",9]],[[/$/g,null,-2],[/>/g,"sh_string",-2]],[[/$/g,null,-2],[/\\(?:\\|")/g,null,-1],[/"/g,"sh_string",-2]],[[/"/g,"sh_string",-2],[/\\./g,"sh_specialchar",-1]],[[/'/g,"sh_string",-2],[/\\./g,"sh_specialchar",-1]]];

/* Java language (http://shjs.sourceforge.net/lang/sh_java.min.js) */
if(!this.sh_languages){this.sh_languages={}}sh_languages.java=[[[/\b(?:import|package)\b/g,"sh_preproc",-1],[/\/\/\//g,"sh_comment",1],[/\/\//g,"sh_comment",7],[/\/\*\*/g,"sh_comment",8],[/\/\*/g,"sh_comment",9],[/\b[+-]?(?:(?:0x[A-Fa-f0-9]+)|(?:(?:[\d]*\.)?[\d]+(?:[eE][+-]?[\d]+)?))u?(?:(?:int(?:8|16|32|64))|L)?\b/g,"sh_number",-1],[/"/g,"sh_string",10],[/'/g,"sh_string",11],[/(\b(?:class|interface))([ \t]+)([$A-Za-z0-9_]+)/g,["sh_keyword","sh_normal","sh_classname"],-1],[/\b(?:abstract|assert|break|case|catch|class|const|continue|default|do|else|extends|false|final|finally|for|goto|if|implements|instanceof|interface|native|new|null|private|protected|public|return|static|strictfp|super|switch|synchronized|throw|throws|true|this|transient|try|volatile|while)\b/g,"sh_keyword",-1],[/\b(?:int|byte|boolean|char|long|float|double|short|void)\b/g,"sh_type",-1],[/~|!|%|\^|\*|\(|\)|-|\+|=|\[|\]|\\|:|;|,|\.|\/|\?|&|<|>|\|/g,"sh_symbol",-1],[/\{|\}/g,"sh_cbracket",-1],[/(?:[A-Za-z]|_)[A-Za-z0-9_]*(?=[ \t]*\()/g,"sh_function",-1],[/([A-Za-z](?:[^`~!@#$%&*()_=+{}|;:",<.>\/?'\\[\]\^\-\s]|[_])*)((?:<.*>)?)(\s+(?=[*&]*[A-Za-z][^`~!@#$%&*()_=+{}|;:",<.>\/?'\\[\]\^\-\s]*\s*[`~!@#$%&*()_=+{}|;:",<.>\/?'\\[\]\^\-\[\]]+))/g,["sh_usertype","sh_usertype","sh_normal"],-1]],[[/$/g,null,-2],[/(?:<?)[A-Za-z0-9_\.\/\-_~]+@[A-Za-z0-9_\.\/\-_~]+(?:>?)|(?:<?)[A-Za-z0-9_]+:\/\/[A-Za-z0-9_\.\/\-_~]+(?:>?)/g,"sh_url",-1],[/<\?xml/g,"sh_preproc",2,1],[/<!DOCTYPE/g,"sh_preproc",4,1],[/<!--/g,"sh_comment",5],[/<(?:\/)?[A-Za-z](?:[A-Za-z0-9_:.-]*)(?:\/)?>/g,"sh_keyword",-1],[/<(?:\/)?[A-Za-z](?:[A-Za-z0-9_:.-]*)/g,"sh_keyword",6,1],[/&(?:[A-Za-z0-9]+);/g,"sh_preproc",-1],[/<(?:\/)?[A-Za-z][A-Za-z0-9]*(?:\/)?>/g,"sh_keyword",-1],[/<(?:\/)?[A-Za-z][A-Za-z0-9]*/g,"sh_keyword",6,1],[/@[A-Za-z]+/g,"sh_type",-1],[/(?:TODO|FIXME|BUG)(?:[:]?)/g,"sh_todo",-1]],[[/\?>/g,"sh_preproc",-2],[/([^=" \t>]+)([ \t]*)(=?)/g,["sh_type","sh_normal","sh_symbol"],-1],[/"/g,"sh_string",3]],[[/\\(?:\\|")/g,null,-1],[/"/g,"sh_string",-2]],[[/>/g,"sh_preproc",-2],[/([^=" \t>]+)([ \t]*)(=?)/g,["sh_type","sh_normal","sh_symbol"],-1],[/"/g,"sh_string",3]],[[/-->/g,"sh_comment",-2],[/<!--/g,"sh_comment",5]],[[/(?:\/)?>/g,"sh_keyword",-2],[/([^=" \t>]+)([ \t]*)(=?)/g,["sh_type","sh_normal","sh_symbol"],-1],[/"/g,"sh_string",3]],[[/$/g,null,-2]],[[/\*\//g,"sh_comment",-2],[/(?:<?)[A-Za-z0-9_\.\/\-_~]+@[A-Za-z0-9_\.\/\-_~]+(?:>?)|(?:<?)[A-Za-z0-9_]+:\/\/[A-Za-z0-9_\.\/\-_~]+(?:>?)/g,"sh_url",-1],[/<\?xml/g,"sh_preproc",2,1],[/<!DOCTYPE/g,"sh_preproc",4,1],[/<!--/g,"sh_comment",5],[/<(?:\/)?[A-Za-z](?:[A-Za-z0-9_:.-]*)(?:\/)?>/g,"sh_keyword",-1],[/<(?:\/)?[A-Za-z](?:[A-Za-z0-9_:.-]*)/g,"sh_keyword",6,1],[/&(?:[A-Za-z0-9]+);/g,"sh_preproc",-1],[/<(?:\/)?[A-Za-z][A-Za-z0-9]*(?:\/)?>/g,"sh_keyword",-1],[/<(?:\/)?[A-Za-z][A-Za-z0-9]*/g,"sh_keyword",6,1],[/@[A-Za-z]+/g,"sh_type",-1],[/(?:TODO|FIXME|BUG)(?:[:]?)/g,"sh_todo",-1]],[[/\*\//g,"sh_comment",-2],[/(?:<?)[A-Za-z0-9_\.\/\-_~]+@[A-Za-z0-9_\.\/\-_~]+(?:>?)|(?:<?)[A-Za-z0-9_]+:\/\/[A-Za-z0-9_\.\/\-_~]+(?:>?)/g,"sh_url",-1],[/(?:TODO|FIXME|BUG)(?:[:]?)/g,"sh_todo",-1]],[[/"/g,"sh_string",-2],[/\\./g,"sh_specialchar",-1]],[[/'/g,"sh_string",-2],[/\\./g,"sh_specialchar",-1]]];

/* python language (http://shjs.sourceforge.net/lang/sh_python.min.js) */
if(!this.sh_languages){this.sh_languages={}}sh_languages.python=[[[/\b(?:import|from)\b/g,"sh_preproc",-1],[/#/g,"sh_comment",1],[/\b[+-]?(?:(?:0x[A-Fa-f0-9]+)|(?:(?:[\d]*\.)?[\d]+(?:[eE][+-]?[\d]+)?))u?(?:(?:int(?:8|16|32|64))|L)?\b/g,"sh_number",-1],[/\b(?:and|assert|break|class|continue|def|del|elif|else|except|exec|finally|for|global|if|in|is|lambda|not|or|pass|print|raise|return|try|while)\b/g,"sh_keyword",-1],[/^(?:[\s]*'{3})/g,"sh_comment",2],[/^(?:[\s]*\"{3})/g,"sh_comment",3],[/^(?:[\s]*'(?:[^\\']|\\.)*'[\s]*|[\s]*\"(?:[^\\\"]|\\.)*\"[\s]*)$/g,"sh_comment",-1],[/(?:[\s]*'{3})/g,"sh_string",4],[/(?:[\s]*\"{3})/g,"sh_string",5],[/"/g,"sh_string",6],[/'/g,"sh_string",7],[/~|!|%|\^|\*|\(|\)|-|\+|=|\[|\]|\\|:|;|,|\.|\/|\?|&|<|>|\||\{|\}/g,"sh_symbol",-1],[/(?:[A-Za-z]|_)[A-Za-z0-9_]*(?=[ \t]*\()/g,"sh_function",-1]],[[/$/g,null,-2]],[[/(?:'{3})/g,"sh_comment",-2]],[[/(?:\"{3})/g,"sh_comment",-2]],[[/(?:'{3})/g,"sh_string",-2]],[[/(?:\"{3})/g,"sh_string",-2]],[[/$/g,null,-2],[/\\(?:\\|")/g,null,-1],[/"/g,"sh_string",-2]],[[/$/g,null,-2],[/\\(?:\\|')/g,null,-1],[/'/g,"sh_string",-2]]];