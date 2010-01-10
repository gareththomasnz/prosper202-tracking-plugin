<?php 
/*
Plugin Name: Prosper202 Tracking Plugin
Plugin URI:  http://customrequest.com/prosper202-tracking-plugin/
Description: Allows WordPress to use Tracking202 and Prosper202 to track individual pages.
Version: 1.0
Author: Custom Request
Author URI: http://customrequest.com/
*/

global $wpdb;
$pros_tablename = $wpdb->prefix . "pros";
$pros_message = '';
$pros_location = get_settings('siteurl') . '/wp-admin/tools.php?page=' . pros_plugin_basename(__FILE__);
$pros_option_location = get_settings('siteurl') . '/wp-admin/options-general.php?page=' . pros_plugin_basename(__FILE__);

load_plugin_textdomain('pros',
            'wp-content/plugins/' . dirname(pros_plugin_basename(__FILE__)) );

function pros_runInclude ()
{
    $path = ABSPATH . WPINC;
    $incfile = $path . '/pluggable-functions.php';
    $incfile_ella = $path . '/pluggable.php';

    if ( is_readable($incfile) ) { 
        require_once($incfile);      
    }
    else if ( is_readable($incfile_ella) ) { 
        require_once($incfile_ella); 
    }
    else {
        echo "Could not read pluggable.php or pluggable-functions.php under $path/.";
        exit;
    }
}

if ( function_exists('add_action') )
{
    pros_runinclude();
    add_action('init', 'pros_createdb');    
    add_action('admin_menu', 'pros_load_manage_panel');
}

function track_prosper()
{
   global $wpdb, $pros_tablename, $pros_location, $pros_message;
   
   $track_pages = "SELECT trackId,trackName, trackUrl, track_pages FROM ". $pros_tablename . " ORDER BY trackName ASC";
   $results = $wpdb->get_results($track_pages);   
   if ($results)
   {
   	foreach ($results as $result)
     	{
	 $arr = split(",",$result->track_pages);
	 foreach ($arr as $page_id)   
			 { 
			 	if(is_page($page_id))
				{
				echo '<script src="'.$result->trackUrl.'" type="text/javascript"></script>';
				}
			 }
		}	 
	}
}	
	add_action('wp_head', 'track_prosper');



function pros_load_manage_panel () 
{
    add_management_page(__('Prosper202 Tracking Plugin ', 'pros'), __('Prosper Tracking Settings ', 'pros'), 1, pros_plugin_basename(__FILE__), 'pros_manage_panel');
}

function pros_createdb () 
{
    global $pros_tablename,  $wpdb, $userdata, $wp_roles;
    if( $wpdb->get_var("show tables like '$pros_tablename'") != $pros_tablename ) 
    {
        $sql = "CREATE TABLE $pros_tablename (
				trackId INT(11) NOT NULL auto_increment,
				trackName text NOT NULL,
				trackUrl text NOT NULL,
				trackDate date NOT NULL default '0000-00-00',
				track_pages text NOT NULL,
				UNIQUE KEY ID (trackId)
				);";
        require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
        dbDelta($sql);
    }

}

////////////////////////  Logic 

function pros_controller ()
{
    global $pros_message;

    $pros_action = $_POST['pros_action'];
    if ( empty($pros_action) ) { $pros_action = $_GET['pros_action']; }
    if ( empty($pros_action) ) { $pros_action = ''; }

    $pros_message = '';
                  
    switch ($pros_action)
    {
        case 'addpros':
        $trackName = $_POST['prosper-name'];
        $trackUrl = $_POST['prosper-url'];
        $trackDate = date('Y-m-d');
		$track_pages = $_POST['prosper-pages'];
        pros_insert($trackName, $trackUrl, $trackDate,$track_pages);
		
        $pros_message = __('Account Added', 'pros'); 
        break;

        case 'trashbd':
        $trackId = $_GET['id'];
        pros_delete($trackId);
        $pros_message = __('Account Deleted Succefully', 'pros');
        break;

        case 'updatebd':
        $trackId = $_POST['id'];
        $trackName = $_POST['prosper-name'];
        $trackUrl = $_POST['prosper-url'];
        $trackDate = date('Y-m-d');
        $track_pages = $_POST['prosper-pages'];
        pros_update($trackId,$trackName, $trackUrl, $trackDate, $track_pages );
        $pros_message = __('Acoount Edited Succefully', 'pros');
        break;

        case 'setupbd':
        pros_activate();
        $pros_message = __('Database Installed.', 'pros');
        break;
   }
}

function pros_insert ($trackName,$trackUrl,$trackDate,$track_pages)
{
    global $pros_tablename,  $wpdb, $userdata, $whatsign;
		
	 $querystr = "select * from $pros_tablename where trackName='$trackName'";
 	 $pageposts = $wpdb->get_results($querystr, OBJECT);

	if(empty($pageposts) && $trackName != "")
	 {
		 $insert = "INSERT INTO $pros_tablename SET trackName = '" . $trackName . "', trackUrl = '" . $trackUrl . "',  trackDate = '" . $trackDate ."', track_pages =  '".$track_pages."' ";
		$results = $wpdb->query( $insert );
	}
		else
	{
		echo '<div class="error"><p><strong>Failure: </strong>Hey ...,You alreday have a account in that name ..! Please change the account name</p></div> ';
	}
	
}

function pros_update ($trackId,$trackName,$trackUrl,$trackDate,$track_pages)
{
    global $pros_tablename, $wpdb, $userdata, $whatsign;
    $update = "UPDATE $pros_tablename SET trackName = '" . $trackName . "', trackUrl = '" . $trackUrl . "', track_pages = '".$track_pages."' WHERE trackId = '$trackId' ";
    $results = $wpdb->query( $update );
}

function pros_delete ($trackId)
{
    global $pros_tablename,  $wpdb;
    $delete = "DELETE FROM $pros_tablename WHERE trackId = '$trackId'";
    $results = $wpdb->query( $delete ); 
}

function pros_get_pros ($trackId)
{
    global $pros_tablename, $wpdb;

    $edit = "SELECT trackId,trackName,trackUrl,trackDate,track_pages FROM $pros_tablename WHERE trackId = '$trackId' LIMIT 1";
    $result = $wpdb->get_row( $edit );
    return $result;
}

///////////////////////////  UI

/* Display UI to manage Account list  */
function pros_manage_panel()
{
    pros_controller();

    global $wpdb, $pros_tablename, $pros_location, $pros_message;

?>

<?php if ( ! empty($pros_message) ) : ?>
<div id="message" class="updated fade"><p><?php echo $pros_message; ?></p></div>
<?php endif; ?>

<?php
 if($_GET['pros_action'] == 'editbd')
 {
 
    $trackId = $_GET['id'];
    $getPros = pros_get_pros($trackId);
?>
<div class="wrap">
 <h2><?php _e('Edit Account', 'pros') ?></h2>

<form method="post">
	<table width="690" border="0" cellpadding="0" cellspacing="7">
      <tr>
        <td style="color:#003366; font-family:Verdana, Arial, Helvetica, sans-serif; font-size:16px; font-weight:bold;">Account - Name</td>
        <td><input name="prosper-name" id="prosper-name" size="25" value="<?php echo wp_specialchars($getPros->trackName, 1); ?>" /></td>
      </tr>
      <tr>
        <td style="color:#003366; font-family:Verdana, Arial, Helvetica, sans-serif; font-size:16px; font-weight:bold;">&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td style="color:#003366; font-family:Verdana, Arial, Helvetica, sans-serif; font-size:16px; font-weight:bold;"> Page IDs <span style="font-size:12px;">(comma separated no spaces)</span>:</td>
		<td>
       <input name="prosper-pages" id="prosper-pages" size="25" value="<?php echo wp_specialchars($getPros->track_pages, 1); ?>">
          </input>        </td>
      </tr>
      <tr>
       <td style="color:#003366; font-family:Verdana, Arial, Helvetica, sans-serif; font-size:16px; font-weight:bold;"> 
Tracking  domain url:
       <span style="font-size:12px;"> <p>(like=&gt; http://static.tracking202.com/lp/1234567/landing.js)</p> </span></td>
        <td><input name="prosper-url" id="prosper-url" size="25" value="<?php echo wp_specialchars($getPros->trackUrl, 1); ?>">
          </input>        </td>
      </tr>
    </table>
	
	
    <p class="submit">
	<input type="hidden" name="id" value="<?php echo wp_specialchars($getPros->trackId, 1); ?>" />
      <input type="hidden" name="pros_action" value="updatebd" />
      <input type="submit" name="submit" value="<?php _e('Update Account', 'pros') ?>" />
    </p>
 </form>



 <p><a href="<?php echo $pros_location; ?>"><?php _e('&laquo; Return to Account list', 'pros'); ?></a></p>
</div>
<?php        
 }
 else 
 {
?>
<div class="wrap">
    <h2><?php _e('New Tracking Account', 'pros') ?></h2>
    <form name="addaccount" id="addaccount" method="post">

<table width="690" border="0" cellpadding="0" cellspacing="7">
      <tr>
        <td style="color:#003366; font-family:Verdana, Arial, Helvetica, sans-serif; font-size:16px; font-weight:bold;">Account - Name</td>
        <td><input name="prosper-name" id="prosper-name" size="25" value="" /></td>
      </tr>
      <tr>
        <td style="color:#003366; font-family:Verdana, Arial, Helvetica, sans-serif; font-size:16px; font-weight:bold;">&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td style="color:#003366; font-family:Verdana, Arial, Helvetica, sans-serif; font-size:16px; font-weight:bold;"> Page IDs <span style="font-size:12px;">(comma separated no spaces)</span>:</td>
		<td>
       <input name="prosper-pages" id="prosper-pages" size="25" value="">
          </input>        </td>
      </tr>
      <tr>
       <td style="color:#003366; font-family:Verdana, Arial, Helvetica, sans-serif; font-size:16px; font-weight:bold;"> 
Tracking  domain url:
       <span style="font-size:12px;"> <p>(like=&gt; http://static.tracking202.com/lp/1234567/landing.js)</p> </span></td>
        <td><input name="prosper-url" id="prosper-url" size="25" value="">
          </input>        </td>
      </tr>
    </table>
            <p class="submit">
              <input type="hidden" name="pros_action" value="addpros" />
              <input type="submit" name="submit" value="<?php _e('Create Account &raquo;', 'pros') ?>" />
            </p>
 </form>
</div>

<div class="wrap">   
<h2><?php _e('Account List', 'pros'); ?></h2> 
<table class="widefat" id="bday-list" width="100%" cellpadding="3" cellspacing="3" >
  <thead>
  <tr>
     <th><?php _e('Name', 'pros'); ?></th>
     <th><?php _e('Url', 'pros'); ?></th>
     <th><?php _e('Date', 'pros'); ?></th>
     <th colspan='2'><?php _e('Action', 'pros'); ?></th>
  </tr>
  </thead>
  <tbody>
<?php
   $sql = "SELECT trackId,trackName, trackUrl, trackDate FROM ". $pros_tablename . " ORDER BY trackName ASC";
   $results = $wpdb->get_results($sql);   
   if ($results)
   {
     foreach ($results as $result)
     {
       $class = ('alternate' == $class) ? '' : 'alternate';

       $edit = '<a href="' . $pros_location . '&pros_action=editbd&id='.
               $result->trackId . '" class="edit">'.__('Edit', 'pros') . '</a></td><td>'.
               '<a href="' . $pros_location . '&pros_action=trashbd&id='.
               $result->trackId . '" class="delete">'.__('Delete', 'pros') . '</a>';
       
       echo "<tr id=\"pros-{$result->trackId}\" class=\"$class\">
       <td>{$result->trackName}</td>
       <td>{$result->trackUrl}</td>
       <td>{$result->trackDate}</td>
       <td>$edit</td>
       </tr>";
     }
   } 
   else
   {
     echo '<tr><td colspan="3">'.__('There are no accounts added...', 'pros').'</td></tr>';
   }
?>
  </tbody>
</table>      
	<table align="left" cellpadding="3">
    <tr>
      <td><em>Prosper202 Tracking Plugin by <a href="http://customrequest.com">Custom Request Internet Marketing</a></em></td>
      <td>If you enjoyed this plugin donations are welcome.</td>
      <td>
<form action="https://www.paypal.com/cgi-bin/webscr" method="post" enctype="multipart/form-data" name="frm">
<input type="hidden" name="cmd" value="_xclick">
<input type="hidden" name="business" value="sales@customrequest.com">
<input type="hidden" name="item_name" id="item_name" value="Encourage Me!">
<input type="hidden" name="item_number" value="1">
<input type="hidden" name="amount" value="">
<input type="hidden" name="no_shipping" value="0">
<input type="hidden" name="no_note" value="1">
<input type="hidden" name="currency_code" value="USD">
<input type="hidden" name="lc" value="US">
<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="Donate To Encourage Me">
<input type="hidden" name="return" value="" />
<input type="hidden" name="rm" value="2">
</form>
<tr>
<tr>
<td><em>Do you like cigarettes?  I don't anymore. <a href="http://cigarettereplacer.com">Cigarette Replacer</a></em></td>
</td>
    </tr>
  </table>
</div>

<?php 
  }
}

//track_prosper($trackUrl,$track_pages);
/*function track_prosper($trackUrl,$track_pages)
{
$arr = split(",",get_option('track_pages'));
foreach ($arr as $page_id)   
 { 
	if(is_page($page_id))
	{
	echo '<script src="'.$trackUrl.'" type="text/javascript"></script>';
	}
 }
}*/
	
/////////////////////////  Misc


// Replace plugin_basename() in WP, taken from <http://trac.wordpress.org/ticket/4408>
function pros_plugin_basename ($file) 
{
    $file = str_replace('\\','/',$file); // sanitize for Win32 installs
    $file = preg_replace('|/+|','/', $file); // remove any duplicate slash
    $file = preg_replace('|^.*/wp-content/plugins/|','',$file); // get relative path from plugins dir
    return $file;
}


?>
