<?php
require_once('./peerlib/peerutils.inc');
require_once('component.php');
$page = new PageContainer();
$html= new HtmlContainer('<html>');
$page->setTitle('Welcome to peerweb');
$page->setBodyTag("<body id='body'>");
$navtop = new HtmlContainer("<div id='navtop' class='navopening'>");
$navtop->add(new Component('<h1>Hello World</h1>'));
$navmid = new HtmlContainer("<div id='navmid'>");
$navcol = new HtmlContainer("<div id='navcol' class='navcol'>");
// force page log
$dbConn->Execute("bogus sql");
$navcol->add(new 
	      Component(
			"<table class='navcol'>
			    <tr><th class='navcol'><img src='".IMAGEROOT."/filter.png' alt=''>Link1</th></tr>
			    <tr><td class='navcol selected'><img src='".IMAGEROOT."/attach.gif' alt=''>Link1</td></tr>
			    <tr><td class='navcol'><img src='".IMAGEROOT."/editcut.gif' alt=''>Link1</td></tr>
</table>"));
// test if can add som more to navcol after adding navcol to parent.
$navcol->add( $gif1=new Component("<img src='".IMAGEROOT."/fireworks015.gif' alt=''>") );
$navmid->add( $navcol );
$navcol->add( $gif2=new Component("<img src='".IMAGEROOT."/fireworks015.gif' alt='' style='background:black;'>") );
$navmain= new HtmlContainer("<div id='navmain' class='navmain'>");
$navmain->add(new Component("<h1 class='normal'>Main</h1>"));
$navmain->add(new Component("
<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Praesent
tempor. Nulla vel tortor. Suspendisse tristique viverra urna. Proin tristique
suscipit magna. Donec ut elit. Quisque ligula enim, sollicitudin vel, mollis
quis, tincidunt sit amet, enim. Maecenas elit. Sed viverra leo at
pede. Phasellus vestibulum semper tellus. Ut facilisis. Fusce sed odio. </p>
"));
$navmain->add(new Component("
<p>Duis auctor massa sed turpis. Ut hendrerit urna. Sed vitae leo et neque
elementum varius. Aenean ac nunc. Nulla congue. Praesent turpis nulla, tempus
vitae, rutrum quis, pellentesque a, orci. Vestibulum placerat pellentesque
mi. Vivamus cursus sapien nec nunc. Cras dolor lectus, vestibulum vel,
imperdiet at, lobortis eu, mi. Pellentesque a libero. Sed ornare. Integer
feugiat luctus ipsum. Nulla ante. </p>
<h2>Factum est</h2>
<p>Sed vestibulum ullamcorper justo. Etiam eu sapien. Proin elit magna, varius
quis, faucibus a, congue sit amet, mi. Nam accumsan justo sed sem. Vestibulum
nibh ipsum, laoreet et, congue eget, posuere quis, lacus. Vivamus neque tellus,
convallis sit amet, rhoncus ac, accumsan et, nulla. Vestibulum eu
sapien. Praesent eu massa at neque molestie porta. Nullam turpis. Nullam
bibendum quam vitae enim. Suspendisse ligula turpis, mollis at, consectetuer
sit amet, venenatis eu, dolor. Maecenas eu augue. Praesent vitae mauris. Proin
rhoncus tincidunt eros. </p>
 
<h3>Platea dictumst</h3>
<p>In hac habitasse platea dictumst. In adipiscing justo eget lacus. Ut eget lacus
quis purus posuere porttitor. In pretium. Etiam vulputate elit non purus. Donec
ac lacus et lectus ultricies cursus. Quisque erat turpis, blandit nec, nonummy
eu, auctor ornare, justo. Cras adipiscing pulvinar eros. Nullam dolor metus,
suscipit nec, ullamcorper id, posuere ut, wisi. Donec justo mauris, scelerisque
id, feugiat eu, dictum ut, lacus. Nunc orci lectus, laoreet placerat, nonummy
euismod, rhoncus in, tellus. Aenean feugiat est vel purus. Fusce tempor. Sed
augue. </p>

Pellentesque malesuada arcu lacinia dui. Nunc feugiat ligula eget magna. Proin
varius. Duis eget quam. Ut non nibh. Nam a felis. Class aptent taciti sociosqu
ad litora torquent per conubia nostra, per inceptos hymenaeos. Cras quis metus
in sapien tempor dignissim. Quisque id metus. Sed sed tellus et metus lacinia
ultricies. Fusce venenatis diam sed augue. Nunc libero diam, consequat in,
placerat et, rhoncus eu, tellus. Nullam mattis ultricies velit. Donec at nisl a
mauris vulputate accumsan. Suspendisse non lorem. 
<p>Duis auctor massa sed turpis. Ut hendrerit urna. Sed vitae leo et neque
elementum varius. Aenean ac nunc. Nulla congue. Praesent turpis nulla, tempus
vitae, rutrum quis, pellentesque a, orci. Vestibulum placerat pellentesque
mi. Vivamus cursus sapien nec nunc. Cras dolor lectus, vestibulum vel,
imperdiet at, lobortis eu, mi. Pellentesque a libero. Sed ornare. Integer
feugiat luctus ipsum. Nulla ante. </p>
<h2>Factum est</h2>
<p>Sed vestibulum ullamcorper justo. Etiam eu sapien. Proin elit magna, varius
quis, faucibus a, congue sit amet, mi. Nam accumsan justo sed sem. Vestibulum
nibh ipsum, laoreet et, congue eget, posuere quis, lacus. Vivamus neque tellus,
convallis sit amet, rhoncus ac, accumsan et, nulla. Vestibulum eu
sapien. Praesent eu massa at neque molestie porta. Nullam turpis. Nullam
bibendum quam vitae enim. Suspendisse ligula turpis, mollis at, consectetuer
sit amet, venenatis eu, dolor. Maecenas eu augue. Praesent vitae mauris. Proin
rhoncus tincidunt eros. </p>
 
<h3>Platea dictumst</h3>
<p>In hac habitasse platea dictumst. In adipiscing justo eget lacus. Ut eget lacus
quis purus posuere porttitor. In pretium. Etiam vulputate elit non purus. Donec
ac lacus et lectus ultricies cursus. Quisque erat turpis, blandit nec, nonummy
eu, auctor ornare, justo. Cras adipiscing pulvinar eros. Nullam dolor metus,
suscipit nec, ullamcorper id, posuere ut, wisi. Donec justo mauris, scelerisque
id, feugiat eu, dictum ut, lacus. Nunc orci lectus, laoreet placerat, nonummy
euismod, rhoncus in, tellus. Aenean feugiat est vel purus. Fusce tempor. Sed
augue. </p>

Pellentesque malesuada arcu lacinia dui. Nunc feugiat ligula eget magna. Proin
varius. Duis eget quam. Ut non nibh. Nam a felis. Class aptent taciti sociosqu
ad litora torquent per conubia nostra, per inceptos hymenaeos. Cras quis metus
in sapien tempor dignissim. Quisque id metus. Sed sed tellus et metus lacinia
ultricies. Fusce venenatis diam sed augue. Nunc libero diam, consequat in,
placerat et, rhoncus eu, tellus. Nullam mattis ultricies velit. Donec at nisl a
mauris vulputate accumsan. Suspendisse non lorem. 
"));
$navmid->add($navmain);
$page->addBodyComponent($navtop);
$page->addBodyComponent($navmid);
$page->show();
?>
