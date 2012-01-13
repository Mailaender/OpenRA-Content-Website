<?PHP

class header
{
    public static function main()
    {
	header::comment();
	header::delete_comment();
	header::upload_guide();
	header::edit_guide();
	header::fav();
	header::delete_item();
	header::apply_filter();
	header::following();
    }

    public static function comment()
    {
	if( isset($_POST['message']))
	{
	    if (user::online())
	    {
		if (trim($_POST['message']) != "")
		{
		    db::executeQuery( "INSERT INTO comments (title, content, user_id, table_id, table_name) VALUES (?,?,?,?,?)", array("", $_POST['message'], user::uid(), $_GET['id'], $_GET['table']) );
		    misc::event_log(user::uid(), "comment", $_GET['table'], $_GET['id']);
		    misc::increase_experiance(5);
		    header("Location: {$_SERVER['HTTP_REFERER']}");
		}
	    }
	}
    }
    
    public static function delete_comment()
    {
	if ( isset($_GET['delete_comment']) and isset($_GET['user_comment']) )
	{
	    $id = $_GET['delete_comment'];
	    $user = $_GET['user_comment'];
	    misc::delete_comment($id, $user);
	    misc::event_log($user, "delete_comment", $_GET["table_name"], $_GET["table_id"]);
	    header("Location: {$_SERVER['HTTP_REFERER']}");
	}
    }
    
    public static function upload_guide()
    {
	if( isset($_POST['upload_guide_title']) && isset($_POST['upload_guide_text']) && isset($_POST['upload_guide_type']))
	{
	    if (user::online())
	    {
		if (trim($_POST['upload_guide_text']) != "" && trim($_POST['upload_guide_title']) != "" && trim($_POST['upload_guide_type'] != ""))
		{
		    $text = nl2br($_POST['upload_guide_text']);
		    db::executeQuery("INSERT INTO guides (title, html_content, guide_type, user_id) VALUES ('".$_POST['upload_guide_title']."','".$text."','".$_POST['upload_guide_type']."',".user::uid().")");
		    misc::increase_experiance(50);
		    $row = db::nextRowFromQuery(db::executeQuery("SELECT uid FROM guides WHERE user_id = ".user::uid()." ORDER BY posted DESC LIMIT 1"));
		    misc::event_log(user::uid(), "add", "guides", $row["uid"]);
		    header("Location: index.php?p=detail&table=guides&id=".$row["uid"]);
		}
	    }
	}
    }
    
    public static function edit_guide()
    {
	if( isset($_POST['edit_guide_title']) && isset($_POST['edit_guide_text']) && isset($_POST['edit_guide_type']) && isset($_POST['edit_guide_uid']))
	{
	    if (user::online())
	    {
		if (trim($_POST['edit_guide_text']) != "" && trim($_POST['edit_guide_title']) != "" && trim($_POST['edit_guide_type'] != "") && trim($_POST['edit_guide_uid'] != ""))
		{
		    $text = nl2br($_POST['edit_guide_text']);
		    db::executeQuery("UPDATE guides SET title = '".$_POST['edit_guide_title']."' WHERE uid = " . $_POST['edit_guide_uid']);
		    db::executeQuery("UPDATE guides SET html_content = '".$text."' WHERE uid = " . $_POST['edit_guide_uid']);
		    db::executeQuery("UPDATE guides SET guide_type = '".$_POST['edit_guide_type']."' WHERE uid = " . $_POST['edit_guide_uid']);
		    misc::event_log(user::uid(), "edit", "guides", $_POST['edit_guide_uid']);
		    header("Location: {$_SERVER['HTTP_REFERER']}");
		}
	    }
	}
    }
    
    public static function fav()
    {
	if ( isset($_GET["table"]) && isset($_GET["id"]) )
	{
	    if (user::online())
	    {
		if(isset($_GET["fav"]))
		{
		    if( db::nextRowFromQuery(db::executeQuery("SELECT * FROM fav_item WHERE table_name = '".$_GET["table"]."' AND table_id = ".$_GET["id"]." AND user_id = " . user::uid())) )
		    {
			db::executeQuery("DELETE FROM fav_item WHERE table_name = '".$_GET["table"]."' AND table_id = ".$_GET["id"]." AND user_id = ".user::uid());
			misc::event_log(user::uid(), "unfav", $_GET["table"], $_GET["id"]);
		    }
		    else
		    {
			db::executeQuery("INSERT INTO fav_item (user_id,table_name,table_id) VALUES (".user::uid().",'".$_GET["table"]."','".$_GET["id"]."')");
			misc::event_log(user::uid(), "fav", $_GET["table"], $_GET["id"]);
		    }
		    header("Location: {$_SERVER['HTTP_REFERER']}");
		}
		else if(isset($_GET["report"]))
		{
		    if( db::nextRowFromQuery(db::executeQuery("SELECT * FROM reported WHERE table_name = '".$_GET["table"]."' AND table_id = ".$_GET["id"]." AND user_id = " . user::uid())) )
		    { } else {
			db::executeQuery("INSERT INTO reported (table_name, table_id, user_id) VALUES ('".$_GET["table"]."', ".$_GET["id"].", ".user::uid() . ")");
			misc::event_log(user::uid(), "report", $_GET["table"], $_GET["id"]);
		    }
		}
	    }
	}
    }
    
    public static function delete_item()
    {
	if ( isset($_GET['del_item']) and isset($_GET['del_item_table']) and isset($_GET['del_item_user']))
	{
	    $item_id = $_GET['del_item'];
	    $table_name = $_GET['del_item_table'];
	    $user_id = $_GET['del_item_user'];
	    misc::delete_item($item_id, $table_name, $user_id);	//delete item and comments related to it
	    misc::event_log(user::uid(), "delete_item", $table_name, $item_id);
	    header("Location: /index.php?p=$table_name");
	}
    }
    
    public static function apply_filter()
    {
	if (isset($_POST["apply_filter"]))
	{
	    if ($_POST["apply_filter_type"] == "map")
	    {
		setcookie("map_sort_by", $_POST["sort"], time()+3600*24*360, "/");
		setcookie("map_mod", $_POST["mod"], time()+3600*24*360, "/");
		setcookie("map_tileset", $_POST["tileset"], time()+3600*24*360, "/");
		$keys = array_keys($_GET);
		$gets = "";
		foreach($keys as $key)
		{
		    if($key != "current_grid_page_maps")
			$gets .= "&" . $key . "=" . $_GET[$key];
		}
		header("Location: /index.php?current_grid_page_maps=1".$gets);
	    }
	    else
	    {
		$arg = $_POST["apply_filter_type"];
		setcookie($arg."_sort_by", $_POST["sort"], time()+3600*24*360, "/");
		setcookie($arg."_type", $_POST["type"], time()+3600*24*360, "/");
		$keys = array_keys($_GET);
		$gets = "";
		foreach($keys as $key)
		{
		    if($key != "current_grid_page_maps")
			$gets .= "&" . $key . "=" . $_GET[$key];
		}
		header("Location: /index.php?current_grid_page_maps=1".$gets);
	    }
	}
    }
    
    public static function following()
    {
	if (isset($_GET["follow"]))
	{
	    $id = $_GET["follow"];
	    if (user::online())
	    {
		$query = "SELECT * FROM following WHERE who = ".user::uid()." and whom = ".$id;
		$result = db::executeQuery($query);
		while (db::nextRowFromQuery($result))
		{
		    return;
		}
		//check if users exists
		$query = "SELECT uid FROM users WHERE uid = ".$id;
		$result = db::executeQuery($query);
		while (db::nextRowFromQuery($result))
		{
		    $query = "INSERT INTO following
				(who,whom)
			    VALUES
			    (
			    ".user::uid().",".$id."
			    )
		    ";
		    db::executeQuery($query);
		    misc::event_log(user::uid(), "follow", "", $id);
		    header("Location: {$_SERVER['HTTP_REFERER']}");
		}
	    }
	}
	elseif(isset($_GET["unfollow"]))
	{
	    $id = $_GET["unfollow"];
	    if (user::online())
	    {
		$query = "SELECT * FROM following WHERE who = ".user::uid()." AND whom = ".$id;
		$result = db::executeQuery($query);
		while (db::nextRowFromQuery($result))
		{
		    //check if users exists
		    $query = "SELECT uid FROM users WHERE uid = ".$id;
		    $result = db::executeQuery($query);
		    while (db::nextRowFromQuery($result))
		    {
			$query = "DELETE FROM following WHERE who = ".user::uid()." AND whom = ".$id;
			db::executeQuery($query);
			misc::event_log(user::uid(), "unfollow", "", $id);
			header("Location: {$_SERVER['HTTP_REFERER']}");
		    }
		}
	    }
	}
    }
}

?>