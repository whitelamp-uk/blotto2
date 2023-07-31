

(
    function () {
        var ball,balls,format,i,num,nums,url,xhttp;
        format          = document.getElementById ('lottery-winners-latest');
        if (!format) {
            return;
        }
        format          = format.dataset.dateformat;
        xhttp           = new XMLHttpRequest ();
        xhttp.onreadystatechange = function ( ) {
            if (this.readyState==4 && this.status==200) {
                var element;
                element = document.getElementById ('lottery-winners-latest');
                element.innerHTML = xhttp.responseText;
                nums = document.body.querySelectorAll ('#lottery-results-latest-table tbody td:nth-child(2)');
                for (num of nums) {
                    balls = num.textContent;
                    num.textContent = '';
                    for (i=0;i<balls.length;i++) {
                        ball = document.createElement ('span');
                        ball.classList.add ('ball');
                        ball.textContent = balls.charAt (i);
                        num.appendChild (ball);
                    }
                }
            }
        };
        // Date format: https://www.php.net/manual/en/datetime.format.php
        url             = new URL(document.currentScript.src);
        url             = url.origin + url.pathname.replace('/media/winners.js','/winners.php');
        xhttp.open ('GET',url+'?f='+encodeURIComponent(format),true);
        xhttp.send ();
    }
) ();


