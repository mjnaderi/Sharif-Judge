/**
 * Wecode judge
 * @file submit_page.js
 * @author truongan
 */

function getCookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for(var i = 0; i <ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length,c.length);
        }
    }
    return "";
}
function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    var expires = "expires="+ d.toUTCString();
    document.cookie = cname + "=" + cvalue + "; " + expires;
} 

var editor = ace.edit("editor");

var theme = getCookie("code_theme");
if (theme == "") theme = "dawn";

editor.setTheme("ace/theme/" + theme);
$("#theme").val(theme);

editor.session.setMode("ace/mode/c_cpp");
$("form").submit(function(){
	$("textarea").val(editor.getSession().getValue());
});

$("select[name=language]").change(function(){
	var lang_to_mode = {"C++":"c_cpp"
		, Java:"java"
		, "Python 2":"python"
		, "Python 3":"python"
	};
	editor.session.setMode("ace/mode/" + lang_to_mode[$(this).val()]);
});

$("#theme").change(function(){
	t = $(this).val();
	editor.setTheme("ace/theme/" + t);
	setCookie('code_theme', t, 30);
});