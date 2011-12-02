<?PHP
include_once("hub.php");
include_once("content.php");

content::head();
?>

<!-- wrap -->
<div id="wrap">

    <?PHP
    content::body_head();
    ?>

    <!-- featured -->		
    <div id="featured">			

	<?PHP

	$res = db::executeQuery("SELECT * FROM maps");
	echo content::createFeaturedItems($res,"maps");

	?>		

    <!-- /featured -->
    </div>

	<!-- content -->
	<div id="content-wrap" class="clear">

	    <div id="content">


		<!-- main -->
		<div id="main">
		    <?PHP
		    
		    if (isset($_GET['register']) and (!user::online()))
		    {
			user::register_actions();
		    }
		    elseif (isset($_GET['recover']) and (!user::online()))
		    {
			echo "<a href='index.php?recover&recover_pass'>Recover Password</a><br>";
			echo "<a href='index.php?recover&recover_user'>Recover Username</a><br>";
			user::recover();
		    }
		    elseif (isset($_GET['p']))
		    {
			if ($_GET['p'] == "profile")
			{
			    if (user::online())
			    {
				profile::show_profile();
			    }
			    else
			    {
				echo "<h3>You are not logged in</h3>";
			    }
			}
			else
			{
			    content::page($_GET['p']);
			}
			
		    }
		    elseif (isset($_GET['action']))
		    {
			// non menu or profile: other pages
			content::action($_GET['action']);
		    }
		    else
		    {
			echo "<h3>Recent Articles</h3>";
		    }
		    //content::createArticleItems($result);
		    
		    ?>
		<!-- /main -->
		</div>
		
		<!-- sidebar -->
		<div id="sidebar">

		    <div class="sidemenu">
			<h3>Sidebar Menu</h3>
			<ul>				
			    <li><a href="index.php">Link</a></li>
			</ul>	
		    </div>

		    <h3>Gallery</h3>

		    <p class="thumbs">
			<?PHP 
			
			//db::createImageGallery($result)
			
			?>
		    </p>					

		<!-- /sidebar -->				
		</div>		
			
	    <!-- /content -->	
	    </div>
	<!-- /content-wrap -->	
	</div>
<!-- /wrap -->
</div>

<!-- footer -->
<div id="footer">	

    <!-- footer-outer -->	
    <?PHP
    echo content::create_footer();
    ?>

<!-- /footer -->
</div>

</body>
</html>
