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

	if (count($_GET) == 0)
	{
	    
	    $res = db::executeQuery("SELECT * FROM featured");
	    echo content::createFeaturedItems($res);
	}
	?>		

    <!-- /featured -->
    </div>

	<!-- content -->
	<div id="content-wrap" class="clear">

	    <div id="content">


		<!-- main -->
		<div id="main">
		    <?PHP

		    pages::main_page_request();
		    
		    ?>
		<!-- /main -->
		</div>
		
		<!-- sidebar -->
		<div id="sidebar">

		    <div class="sidemenu">
			<h3><? echo lang::$lang['sidebar menu']; ?></h3>
			<ul>				
			    <li><a href="index.php"><? echo lang::$lang['link']; ?></a></li>
			</ul>	
		    </div>

		    <h3><? echo lang::$lang['gallery']; ?></h3>

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
