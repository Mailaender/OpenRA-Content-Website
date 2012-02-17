<?PHP

class upload
{
    public static function upload_oramap($username)
    {
	if(isset($_FILES["map_upload"]["name"]))
	{
	    if (is_uploaded_file($_FILES["map_upload"]["tmp_name"]))
	    {
		$filename = $_FILES["map_upload"]["name"];
		$source = $_FILES["map_upload"]["tmp_name"];
		$type = $_FILES["map_upload"]["type"];
		$name = explode(".", $filename);
		$accepted_type = "application/octet-stream";
		if ($type != $accepted_type)
		{
		    return "Not supported file type";	// that's not a map file
		}
		if (strtolower($name[1]) != "oramap")
		{
		    return "Not supported file type";	// that's not a map file (map file must have `oramap` extention)
		}
		exec("python python/ml.py -s " . str_replace(" ", "\ ", $source) . " -i " . user::uid() . " -u " . user::username() . " -t " . str_replace(" ", "\ ", $filename), $output, $return_code);
		function code_match($code)
		{
		    $codes = array(
			'0' => "0",
			'1' => "Error's while uploading map, contact administrator",
			'2' => "Incorrect options",
			'3' => "Unknown map format",
			'4' => "Unknown mod",
			'5' => "Map already exists",
			'6' => "Could not upload the map",
			'7' => "Database error, try again later",
		    );
		    return $codes[$code];
		}
		//return codes:
		// 0  -  Success
		// 1  -  Other errors
		// 2  -  Incorrect options
		// 3  -  Unknown map format
		// 4  -  Unknown mod
		// 5  -  Map exists
		// 6  -  Could not upload map
		// 7  -  Database error
		if ($return_code == 0)
		    misc::increase_experiance(10);
		    $row = db::nextRowFromQuery(db::executeQuery("SELECT uid FROM maps WHERE user_id = ".user::uid()." ORDER BY posted DESC LIMIT 1"));
		    misc::event_log(user::uid(), "add", "maps", $row["uid"]);
		return code_match($return_code);
	    }
	    else
	    {
		return "";
	    }
	}
	else
	{
	    return "";	// file is not choosen
	}
    }
    
    public static function upload_unit($username)
    {
	function insert_unit($dirname,$description,$type)
	{
	    $query = "INSERT INTO units
		(title,description,preview_image,user_id,screenshot_group_id,type)
		VALUES
		(?,?,?,?,?,?)
		";
	    db::executeQuery($query, array($dirname, $description, "users/".user::username()."/units/".$dirname."/preview.gif", user::uid(), 0, $type));
	    misc::increase_experiance(50);
	    $row = db::nextRowFromQuery(db::executeQuery("SELECT uid FROM units WHERE user_id = ".user::uid()." ORDER BY posted DESC LIMIT 1"));
	    misc::event_log(user::uid(), "add", "units", $row["uid"]);
	}
	$unit_palette = "temperat.pal"; //Needed for shp extractor
	if(isset($_POST['unit_palette']))
	    $unit_palette = $_POST['unit_palette'];
	$count = 0;
	$messages = "";
	while (isset($_FILES["file_".$count]))
	{
	    $run_shp = false;
	    $filename = $_FILES["file_".$count]["name"];
	    if ($filename == "")
		return $messages;

	    if (!isset($_POST["unit_name"]) or $_POST["unit_name"] == "")
		return "Name of unit is not set!";
	    $dirname = $_POST["unit_name"];
	    
	    $description = "";
	    if (isset($_POST["unit_description"]))
		$description = $_POST["unit_description"];
	    
	    $unit_type = "other";
	    if (isset($_POST["unit_type"]))
		$unit_type = $_POST["unit_type"];
	    
	    $source = $_FILES["file_".$count]["tmp_name"];
	    $type = $_FILES["file_".$count]["type"];
	    $name = explode(".", $filename);	//array

	    $accepted_types = array("application/octet-stream","application/x-qgis");
	    $accepted_exts = array("shp","yaml");
	    if(!in_array($type, $accepted_types) or !in_array(strtolower($name[1]), $accepted_exts))
	    {
		$messages .= $filename . " - upload fail: not supported file type<br>";
		continue;
	    }

	    $path = WEBSITE_PATH . "users/" . $username . "/units/" . $dirname;
	    if ($count == 0)	//make dir checking at first file recognised
	    {
		if (is_dir($path))
		{
		    return "Unit with such name already exists";
		}
		else
		{
		    mkdir($path);
		    insert_unit($dirname, $description, $unit_type);
		}
	    }
	    else
	    {
		//directory was not created before this moment - file types were unsupported
		if (!is_dir($path))
		{
		    mkdir($path);
		    insert_unit($dirname, $description, $unit_type);
		}
	    }
	    
	    $target_path = $path . "/" . $filename;
	    if(move_uploaded_file($source, $target_path))
	    {
		$messages .= $filename ." - uploaded<br>";
	    }
	    if (strtolower($name[1]) == "shp" and $run_shp == false)
	    {
		exec("mono mono/src/SHPExtractor/bin/Debug/SHPExtractor.exe  -filename=\"".$target_path."\" -palette=\"".$unit_palette."\"");
		$run_shp = true;
	    }
	    $count++;
	}
	return $messages;
    }
    
    public static function avatar()
    {
	if(isset($_FILES["avatar_upload"]["name"]))
	{
	    if (!is_uploaded_file($_FILES["avatar_upload"]["tmp_name"]))
		return "";
	    $filename = $_FILES["avatar_upload"]["name"];
	    $source = $_FILES["avatar_upload"]["tmp_name"];
	    $type = $_FILES["avatar_upload"]["type"];
	    $accepted_types = array("image/jpeg","image/png","image/gif","image/bmp","image/x-png");
	    if(!in_array($type, $accepted_types))
	    {
		return "type error";
	    }
	    move_uploaded_file($source, "users/".user::username()."/avatar_original.jpg");
	    misc::imageresize("users/".user::username()."/avatar.jpg","users/".user::username()."/avatar_original.jpg",200,400,100, $type);
	    unlink("users/".user::username()."/avatar_original.jpg");
	    $query = "UPDATE users SET avatar = ? WHERE uid = ?";
	    db::executeQuery($query, array("Some", user::uid()));
	    return "done";
	}
	return "";
    }
}

class pages
{
    public static function main_page_request()
    {
	if (isset($_GET['register']) and (!user::online()))
	{
	    user::register_actions();
	    return;
	}
	if (isset($_GET['recover']) and (!user::online()))
	{
	    echo "<a href='index.php?recover&recover_pass'>".lang::$lang['recover pw']."</a><br>";
	    echo "<a href='index.php?recover&recover_user'>".lang::$lang['recover usr']."</a><br>";
	    user::recover();
	    return;
	}
	if (isset($_GET['action']))
	{
	    // non menu or profile: other pages
	    content::action($_GET['action']);
	    return;
	}
	// other checks should be done before $_GET['p'], because it will override page
	if (isset($_GET['p']))
	{
	    if ($_GET['p'] == "profile")
	    {
		if (user::online())
		{
		    profile::show_profile();
		}
		else
		{
		    if (isset($_GET["profile"]))
		    {
			profile::show_profile();
		    }
		}
	    }
	    else
	    {
		content::page($_GET['p']);
		return;
	    }
	}

	if (count($_GET) == 0)
	{
	    echo "<h3>".lang::$lang['recent articles']."</h3>";
	    $result = db::executeQuery("SELECT * FROM articles");
	    echo content::createArticleItems($result);

	    return;
	}
	
    }

    public static function current($page, $request)
    {
	if ($page == $request)
	{
	    return "current";
	}
	else
	{
	    return "";
	}
    }
    
    public static function cur_lang($lang)
    {
	if (!isset($_COOKIE['language']))
	    return "";
	if ($lang == $_COOKIE['language'])
	{
	    return "underline_link";
	}
	else
	{
	    return "";
	}
    }
    
    public static function allISSet($arr)
    {
	for($i = 0; $i < count($arr); $i++)
	    if(isset($_POST[$arr[$i]]) == false)
		return false;
	return true;
    }
    
    public static function serialize_array($arr)
    {
	return base64_encode(json_encode($arr)); 
    }
    
    public static function deserialize_array($str)
    {
	return json_decode(base64_decode($str)); 
    }
}

class misc
{
    public static function avatar($user_id)
    {
	$query = "SELECT avatar,login FROM users WHERE uid = ".$user_id;
	$ava = db::nextRowFromQuery(db::executeQuery($query));
	if ($ava["avatar"] == "None")
	{
	    return "images/noavatar.png";
	}
	elseif ($ava["avatar"] == "Some")
	{
	    return "users/".$ava["login"]."/avatar.jpg";
	}
    }
    
    public static function comment_owner($id)
    {
	if (user::online())
	{
	    if ($id == user::uid())
	    {
		return True;
	    }
	}
	return False;
    }
    
    public static function delete_comment($id, $user)
    {
	if ( $user == user::uid() )
	{
	    $query = "DELETE FROM comments WHERE uid = ?";
	    db::executeQuery($query, array($id));
	}
    }
    
    public static function delete_item($item_id, $table_name, $user_id)
    {
	if ($user_id == user::uid())
	{	    
	    //remove map directory and it's content from disk
	    if ($table_name == "maps")
	    {
		$query = "SELECT path FROM maps WHERE uid = ".$item_id;
		$result = db::executeQuery($query);
		while ($db_data = db::fetch_array($result))
		{
		    $path = WEBSITE_PATH . $db_data['path'];
		}
		foreach (scandir($path) as $item)
		{
		    if ($item == '.' || $item == '..') continue;
		    unlink($path.$item);
		}
		rmdir($path);
	    }
	    
	    if ($table_name == "units")
	    {
		$query = "SELECT title FROM units WHERE uid = ".$item_id;
		$result = db::executeQuery($query);
		while ($db_data = db::fetch_array($result))
		{
		    $title = $db_data['title'];
		}
		$query = "SELECT login FROM users WHERE uid = ".$user_id;
		$result = db::executeQuery($query);
		while ($db_data = db::fetch_array($result))
		{
		    $username = $db_data['login'];
		}
		$path = WEBSITE_PATH . "users/" . $username . "/units/" . $title . "/";
		foreach (scandir($path) as $item)
		{
		    if ($item == '.' || $item == '..') continue;
		    unlink($path.$item);
		}
		rmdir($path);
	    }
	    
	    //remove item from DB
	    $query = "DELETE FROM $table_name WHERE uid = ?";
	    db::executeQuery($query, array($item_id));
	    //remove comments from DB
	    //remove records from fav_item table related to current item for each user
	    $tables = array("comments", "fav_item", "featured", "reported");
	    foreach($tables as $table)
	    {
		$query = "DELETE FROM $table WHERE table_name = ? AND table_id = ?";
		db::executeQuery($query, array($table_name, $item_id));
	    }
	}
    }
    
    public static function imageresize($result_file, $original_file, $new_width, $new_height, $quality, $type)
    {
	if($type == "image/jpeg")
	{
	    $im=imagecreatefromjpeg($original_file);
	} 
	elseif($type == "image/png" or $type == "image/x-png")
	{
	    $im=imagecreatefrompng($original_file);
	} 
	elseif($type == "image/gif")
	{
	    $im=imagecreatefromgif($original_file);
	}
	elseif($type == "image/bmp")
	{
	    $im=imagecreatefrombmp($original_file);
	}

	$k1=$new_width/imagesx($im);
	$k2=$new_height/imagesy($im);
	$k=$k1>$k2?$k2:$k1;

	$w=intval(imagesx($im)*$k);
	$h=intval(imagesy($im)*$k);

	$im1=imagecreatetruecolor($w,$h);
	if($type == "image/png" or $type == "image/x-png")
	{
	    imagealphablending($im1, false);
	    imagesavealpha($im1, true);
	}
	imagecopyresampled($im1,$im,0,0,0,0,$w,$h,imagesx($im),imagesy($im));

	if($type == "image/png" or $type == "image/x-png")
	{
	    imagepng($im1,$result_file);
	}
	else
	{
	    imagejpeg($im1,$result_file,$quality);
	}
	imagedestroy($im);
	imagedestroy($im1);
    }

    public static function increase_experiance($points)
    {
	$query = "SELECT experiance FROM users WHERE uid = ".user::uid();
	$value = db::nextRowFromQuery(db::executeQuery($query));
	$value = $value["experiance"] + $points;
	$query = "UPDATE users SET experiance = ? WHERE uid = ?";
	db::executeQuery($query, array($value, user::uid()));
    }
    
    public static function event_log($user_id, $type, $table_name="", $table_id=0)
    {
	// types: add,delete_item,delete_comment,report,fav,unfav,edit,login,logout,comment,follow,unfollow
	// issue with `delete_item`: we can not show what user removed because it's removed completely (even basic info of item)
	$query = "INSERT INTO event_log
		(user_id, type, table_name, table_id)
		VALUES
		(?,?,?,?)
	";
	db::executeQuery($query, array($user_id, $type, $table_name, $table_id));
    }
    
    public static function amount_rows($result, $value)
    {
	if (db::num_rows($result) > $value)
	{
	    return True;
	}
	else
	{
	    return False;
	}
    }
    
    public static function check_cookie_enabled()
    {
	if (isset($_COOKIE["language"]))
	{
	    return true;
	}
	else
	{
	    return false;
	}
    }
    
    public static function minimap($path)
    {
	if (file_exists($path . "minimap.bmp"))
	{
	    return $path . "minimap.bmp";
	}
	else
	{
	    return "images/nominimap.png";
	}
    }
    
    public static function option_selected($value, $request)
    {
	if ($value == $request)
	{
	    return "selected='selected'";
	}
	else
	{
	    return "";
	}
    }
    
    public static function option_selected_bool($value, $request)
    {
	if ($value == $request)
	{
	    return "true";
	}
	else
	{
	    return "false";
	}
    }
    
    public static function item_title_by_uid($id, $table)
    {
	$query = "SELECT title FROM $table WHERE uid = $id";
	$result = db::executeQuery($query);
	while ($row = db::nextRowFromQuery($result))
	    return $row["title"];
	return "";
    }
    
    public static function paging($nrOfPages, $i, $current, $gets, $table)
    {
	if($current == $i)
	    $pages = "<span id='page_count_none'>" . $i . "</span>";
	else
	    $pages = "<span id='page_count'><a href='index.php?current_grid_page_".$table."=".$i.$gets."' style='display:inline;'>" . $i . "</a></span>";
	return $pages;
    }
}

?>
