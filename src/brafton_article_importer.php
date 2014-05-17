<?php 



if ( !class_exists( 'Article_Importer' ) )
{	
	include_once ( plugin_dir_path( __FILE__ ) . '../vendors/SampleAPIClientLibrary/ApiHandler.php');
	include_once 'brafton_article_helper.php';
	include_once 'brafton_taxonomy.php';
	include_once 'brafton_image_handler.php';
	include_once 'brafton_errors.php';
	/**
	 * @package WP Brafton Article Importer 
	 *
	 */
	class Brafton_Article_Importer {

		 public $brafton_article_log;
		 public $brafton_article;
		 public $brafton_images;
		//Initialize 
		function __construct ( 
						Brafton_Image_Handler $brafton_image = Null, 
						Brafton_Taxonomy $brafton_cats, 
						Brafton_Taxonomy $brafton_tags, 
						Brafton_Article_Helper $brafton_article )
		{
			if( 'on' == get_option(BRAFTON_ENABLE_IMAGES) )
			{	//grab image data for previously imported images
				$this->brafton_images = get_option('brafton_images');
				//and load the image class.
				$this->brafton_image_handler = $brafton_image;
			}
			$this->brafton_cats = $brafton_cats;
			$this->brafton_tags = $brafton_tags; 
			$this->brafton_article = $brafton_article; 
			$this->brafton_image = $brafton_image;

			$log['priority'] = 1; 
			brafton_initialize_log( 'brafton_article_log' );

			
		}

		/**
		 * @uses Brafton_Article_Helper to retrieve an articles array containing NewsItem objects.
		 * @uses ApiHandler indirectly through Brafton_Article_Helper to grab article specific metadata from client's xml uri.
		 * @uses NewsItem indirectly through ApiHandler to grab article specific metadata from client's xml uri.
		 * @uses NewsCategory indirectly through NewsItem to grab category id's from client's xml uri.
		 * @uses XMLHandler indirectly through NewsItem to make http requests to client's xml url. 
		 * @uses Brafton_Taxonomy to assign category and tags to Articles
		 * @uses Brafton_Image_handler to attach post thumbnails to Articles
		 * 
		 * 
		 * Imports content from client's xml feed's uri into WordPress. 
		 */
		public function import_articles(){
			//Retrieve articles from feed
			$article_array = $this->brafton_article->get_articles();
			//Retrieve article import log
			$this->brafton_articles_log = get_option( 'brafton_articles_log' );
			foreach( $article_array as $a ){
				//Get article meta data from feed
				$brafton_id = $a->getID(); 
				$post_exists = $this->brafton_article->exists( $brafton_id );
				if( $post_exists == false || get_option( 'braftonxml_overwrite' ) == 'on' )
				{
					$post_date = $this->brafton_article->get_publish_date( $a ); 
					$post_title = $a->getHeadline();
					$post_content = $a->getText(); 
					$photos = $a->getPhotos(); 
					$post_excerpt = $a->getExtract(); 
					$keywords = $a->getKeywords();
					$cats = $a->getCategories(); 
					$tags = $a->getTags();

					//Get more video article meta data
					$post_author = $this->brafton_article->get_post_author(); 
					$post_status = $this->brafton_article->get_post_status();

					$post_status = get_option( 'braftonxml_sched_status' );

					//prepare video article tag id array
					#$input_tags = $this->brafton_tags->get_terms( $tags, 'tag' );

					//prepare video article category id array
					$post_category = $this->brafton_cats->get_video_terms( $cats, 'category' );  

					//prepare single article meta data array
					$article = compact(
								'brafton_id', 
								'post_author', 
								'post_date', 
								'post_content', 
								'post_title', 
								'post_status', 
								'post_excerpt', 
								'post_category'
								/* 'tags_input' */
							); 

					//insert article to WordPress database
					$post_id = $this->brafton_article->insert_article( $article );

					//update post to include thumbnail image
					if ( get_option( 'brafton_enable_images' ) == "on" )
						$this->brafton_image->insert_image( $photos, $post_id );	
				} 
			}
		}

	}

}
?>