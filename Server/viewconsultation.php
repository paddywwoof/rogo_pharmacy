<html>
  <head>
    <script LANGUAGE="Javascript">
      function setdate() {
        var inp = document.getElementById('date');
        if (inp.value.length === 0) {
          var dt = new Date();
          document.getElementById('date').value = '' + dt.getFullYear() + '-' + (dt.getMonth() + 1) + '-' + dt.getDate();
        }
        else {
          iframeload();
        }
      }
      function makegetkey(){
        var word1 = document.getElementById('word1').value;
        var word2 = document.getElementById('word2').value;
        var dt = document.getElementById('date').value;
        return 'word1=' + word1 + '&word2=' + word2 + '&date=' + dt;
      }
      function iframeload() {
        var url = 'consultation.php?' + makegetkey() + '&nb=false';
        document.getElementById('report_frame').src = url;
        //TODO get comment info and populate other fields
        document.getElementById('info1').value = '';
        submitinfo();
      }
      function submitinfo() {
        url = 'save_get_info.php?' + makegetkey();
        var newinfo = document.getElementById('info1').value.trim();
        var re = new RegExp('[^a-zA-Z0-9 !\,\.\(\)\?\+\-]', 'g');
        newinfo = newinfo.replace(re, ' ');
        if (newinfo.length > 0) {
          url = url + '&info1=' + newinfo;
        }
        var xhReq = new XMLHttpRequest();
        xhReq.onreadystatechange = function() {
          if (xhReq.readyState == 4 && xhReq.status == 200) {
            document.getElementById('info1').value = xhReq.responseText;
          }
        }
        xhReq.open("GET", url, true);
        xhReq.send();
      }
    </script>
    <link href="zen.css" rel="stylesheet" type="text/css">
  </head>
  <body id="css-zen-garden" onload="setdate();">
    <div class="page-wrapper">
      <h2>Enter key of consultation report</h2>
      <form name="input" action="mainmenu.php">
       <input type="submit" value="Return to main menu">
       <br />
       <input type="button" value="Load a new report, enter keys on right" onclick="iframeload();">
       word1=<input id="word1" type="text" name="word1" size=5 value="<?php echo $_GET['word1']; ?>">
       word2=<input id="word2" type="text" name="word2" size=5 value="<?php echo $_GET['word2']; ?>"> 
       date (YYYY-MM-DD)=<input id="date" type="text" name="date" size=8 value="<?php echo $_GET['date']; ?>">
       <BR />
       Significant information not captured by the electronic consultation<BR />
       <input type="button" value="Submit information entered in box below" onclick="submitinfo();">
       <textarea id="info1"  style="height:100px; width: 960px;"></textarea>
      </form>
      <iframe id="report_frame" src="" height=400 width = 960>
      </iframe> 
    </div>
  </body>
</html>
