<html>
<head>
	<title>Tiny Tiny RSS</title>
	<link rel="stylesheet" href="tt-rss.css" type="text/css">
	<script type="text/javascript" src="prefs.js"></script>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>

<body onload="init()">

<table width="100%" height="100%" cellspacing=0 cellpadding=0 class="main">
<tr>
	<td class="header" valign="middle" colspan="1">	
			Preferences
	</td>
</tr>
<tr>
	<td class="toolbar" valign="middle" colspan="1">	
		<a class="button">Close</a>
	</td>
</tr>
</tr>
	<td class="content" id="content" valign="top">
		<h2>Feed Configuration</h2>

		<table class="prefAddFeed">
			<tr><td>Title:</td><td><input id="fadd_title"></td></tr>
			<tr><td>Link:</td><td><input id="fadd_link"></td></tr>
			<tr><td colspan="2" align="right">
				<a class="button" href="javascript:addFeed()">Add feed</a></td></tr>
		</table>

		<!-- <input type="submit" value="Add feed"> -->
		
		<div id="feeds">&nbsp;</div>

		<hr>
	</td>
</tr>
<tr>
	<td colspan="2" id="notify" class="notify">
		<a href="">Tiny-Tiny RSS</a> v0.1 &copy; 2005 Andrew Dolgov
	</td>
</td>
</table>


</body>
</html>
