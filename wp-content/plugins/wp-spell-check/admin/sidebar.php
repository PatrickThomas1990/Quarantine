<?php
if(!defined('ABSPATH')) { exit; }
?>

<div style="float: right; width:23%; margin-left: 2%; margin-top: 50px">
				<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.0";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
<script type="text/javascript">
//<![CDATA[
if (typeof newsletter_check !== "function") {
window.newsletter_check = function (f) {
    var re = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-]{1,})+\.)+([a-zA-Z0-9]{2,})+$/;
    if (!re.test(f.elements["ne"].value)) {
        alert("The email is not correct");
        return false;
    }
    for (var i=1; i<20; i++) {
    if (f.elements["np" + i] && f.elements["np" + i].value == "") {
        alert("");
        return false;
    }
    }
    if (f.elements["ny"] && !f.elements["ny"].checked) {
        alert("You must accept the privacy statement");
        return false;
    }
    return true;
}
}
//]]>
</script>

<!--<div style="padding: 5px 5px 10px 5px; border: 3px solid #73019A; border-radius: 5px; background: white;">
<h2>Get on Our Priority Notification List</h2>
<form method="post" action="https://www.wpspellcheck.com/?na=s" onsubmit="return newsletter_check(this)">

<table cellspacing="0" cellpadding="3" border="0">

<tr>
	<th>Email</th>
	<td align="left"><input class="newsletter-email" type="email" name="ne" style="width: 100%;" size="30" required></td>
</tr>

<tr>
	<td colspan="2" class="newsletter-td-submit">
		<input class="newsletter-submit" type="submit" value="Sign me up"/>
	</td>
</tr>

</table>
</form>
</div>
<hr>-->

<div style="padding: 5px 5px 20px 5px;border-radius: 5px;background: white;text-align: center;box-shadow: 0px 0px 10px 0px rgba(0,0,0,0.5);text-align: center;">
    <h2 style="margin-bottom: 25px;">Check Out Our Proofreading and Website Maintenance Packages</h2>
    <a href="https://www.wpspellcheck.com/wp-spell-check-services/?utm_source=baseplugin&utm_campaign=toturial_rightside&utm_medium=spell_check&utm_content=<?php echo $wpsc_version; ?>" target="_blank" style="padding: 8px 30px; background: #008200; color: white; border-radius: 20px; text-decoration: none; font-weight: bold;">Click Here to Learn More</a>
</div>
<hr style="margin: 1em 0;">
<div style="padding: 5px 5px 10px 5px; border-radius: 5px; background: white; box-shadow: 0px 0px 10px 0px rgba(0,0,0,0.5); text-align: center;">
				<a href="https://www.wpspellcheck.com/support/?utm_source=baseplugin&utm_campaign=toturial_rightside&utm_medium=spell_check&utm_content=<?php echo $wpsc_version; ?>" target="_blank"><img src="<?php echo plugin_dir_url( __FILE__ ) . 'images/wp-spellcheck-tutorials.jpg'; ?>" style="max-width: 99%;" alt="Watch WP Spell Check Tutorials" /></a>
</div>
<hr style="margin: 1em 0;">
<div style="padding: 5px 5px 10px 5px; border-radius: 5px; background: white; text-align: center; box-shadow: 0px 0px 10px 0px rgba(0,0,0,0.5); text-align: center;">
				<h2>Follow us on Facebook</h2>
				<div class="fb-page" data-href="https://www.facebook.com/wpspellcheck/" data-width="180px" data-small-header="true" data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="true"><blockquote cite="https://www.facebook.com/wpspellcheck/" class="fb-xfbml-parse-ignore"><a href="https://www.facebook.com/wpspellcheck/">WP Spell Check</a></blockquote></div>
</div>
<hr style="margin: 1em 0;">
<div class="newsletter newsletter-subscription" style="padding: 5px 5px 10px 5px; border-radius: 5px; background: white; box-shadow: 0px 0px 10px 0px rgba(0,0,0,0.5); text-a0lign: center;">
<div class="wpsc-sidebar" style="margin-bottom: 15px; text-align: center;"><h2>Enjoying this plugin?</h2><center>Please help by giving us a <a class="review-button" href="https://wordpress.org/support/plugin/wp-spell-check/reviews/?filter=5" target="_blank">★★★★★ Rating</a></center></div>
</div>
<hr style="margin: 1em 0;">
			</div>