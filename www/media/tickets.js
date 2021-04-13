

(
    function () {
        var url,xhttp;
        xhttp           = new XMLHttpRequest ();
        xhttp.onreadystatechange = function ( ) {
            if (this.readyState==4 && this.status==200) {
                var element;
                element = document.querySelector ('section.lottery-tickets');
                element.innerHTML = xhttp.responseText;
            }
        };
        url             = new URL (document.currentScript.src);
        url             = url.origin + url.pathname.replace ('/media/tickets.js','/tickets.php');
        xhttp.open ('GET',url,true);
        xhttp.send ();
    }
) ();


