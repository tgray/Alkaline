### Themes and the Canvas Engine

Alkaline offers full customization of the look-and-feel of your image library. The various elements that make up your Web site's style: typefaces, colors, sizes, images, and so forth collectively are a theme. By changing your Alkaline theme, you instantly alter all of these elements.

##### Installing Themes

You can download additional themes at the [Alkaline Lounge](/users/). You can install most themes by dragging an extension's folder to your `/themes/` folder, choosing **Configuration > Themes**, and clicking Install Themes. Once your new theme is listed, you can change your theme by choosing **Configuration > Settings**.

##### Designing A Theme

*Note: Familiarity with HTML and CSS is required to design your own theme. You will only be able to create a theme as complex as your mastery of these markup languages.*

First, take a look at a theme in your `/themes/` folder. Basic themes, including the default theme that ships with Alkaline, are a series of HTML files as well as a CSS and XML file. The HTML files contain the basic layout of the page. The CSS file contains all of the font, color, and positioning rules. The XML file contains information Alkaline needs to import your theme--ignore it for now.

Most themes are made up of three files. For example, your theme's home page:

- header.html
- index.html
- footer.html

Your header.html and footer.html files span across your entire library and are a good place to start.

###### Insertions

Insertions are the most basic component of your theme. They tell Alkaline where to insert data, and are found between curved brackets, like so: `{Insertion}`. You can optionally specify and if/else statement if the data is unavailable. For example:

	{if:Image_Title}
		{Image_Title}
	{else:Image_Title}
		This image has no title.
	{/if:Image_Title}

###### Filters

Filters modify the data to be inserted and are appear after the insertion, followed by a pipe, like so: `{Insertion|Filter}`. You can apply multiple filters to a single tag like so: `{Insertion|Filter1|Filter2}`. Here are the current filters:

- `alpha` -- convert number to words (for example, 2 to "two") (U.S. English only)
- `alpha0` -- convert number to words, except zero
- `fit50` -- cut to 50 characters
- `fit100` -- cut to 100 characters
- `fit250` -- cut to 250 character
- `fit500` -- cut to 500 characters
- `fit1000` -- cut to 1,000 characters
- `reltime` -- convert time to a relative time (for example, "3 days ago") (U.S. English only)
- `sterilize` -- strip HTML and PHP tags
- `upperfirst` -- capitalize first word
- `upperwords` -- capitalize all words
- `urlencode` -- encodes content for placement in a URI
- `urlize` -- applies slashes to all non-alphanumeric characters for placement in a URI


###### Blocks

Blocks display a series of data in a loop. There are seven major blocks in Alkaline: Comments, Images, Pages, Posts, Sets, Sizes, and Tags. For example:

	{block:Images}
		{Image_Title}
	{/block:Images}

###### Counts

You can count blocks outside the loop by like so: `{count:Block}`.

###### PHP Constants

You can access PHP constants (created using `define()`) within templates like so `{define:Constant}`. Alkaline will search for case-matching constants first, then all-uppercase, then all-lowercase.

###### Includes

Alkaline lets you include arbitrary HTML, PHP, or other data, like so: `{include:Filename}`.

###### Extension Hooks

Hooks tell Alkaline when to execute extensions. Alkaline extension developers can use this functionality to call their extension and execute code, like so: `{hook:Hookname}`.

##### Slideshow

The standard slideshow can be operated by keyboard shortcuts; use your keyboard's arrow keys to move back and forth and press the `P` key to play or pause the automatic rotation. Additionally, you can add buttons or links to navigate your slideshow by adding classes like so: `<a href="" class="slideshow_next">Next image</a>`.

These are the pre-installed CSS classes:

- `.slideshow_play`
- `.slideshow_stop`
- `.slideshow_next`
- `.slideshow_prev`
- `.slideshow_pause` (toggles play and stop)

##### Insertion Reference

There are nearly a hundred built-in insertions. Themes, extensions, and additional programming can add any number more.

###### Comment

- `comment_id`
- `comment_created`
- `comment_status`
- `comment_text`
- `comment_text_raw`
- `comment_markup`
- `comment_author_name`
- `comment_author_uri`
- `comment_author_email`
- `comment_author_ip`
- `comment_author_avatar`

###### Page

- `page_id`
- `page_title`
- `page_title_url`
- `page_text`
- `page_text_raw`
- `page_markup`
- `page_images`
- `page_views`
- `page_words`
- `page_created`
- `page_created_format`
- `page_modified`
- `page_modified_format`

###### Image

- `image_id`
- `image_ext`
- `image_mime`
- `image_title`
- `image_description`
- `image_description_raw`
- `image_markup`
- `image_privacy`
- `image_name`
- `image_color_r`
- `image_color_g`
- `image_color_b`
- `image_color_h`
- `image_color_s`
- `image_color_l`
- `image_taken`
- `image_taken_format`
- `image_uploaded`
- `image_uploaded_format`
- `image_published`
- `image_published_format`
- `image_updated`
- `image_updated_format`
- `image_geo`
- `image_geo_lat`
- `image_geo_long`
- `image_views`
- `image_comment_count`
- `image_height`
- `image_width`

###### Image (Extra)

These insertions are not available on all of your templates.

- `image_colorkey`
- `image_tags` -- comma-separated tags (or use `{block:Tags}` instead)
- `image_tag_count`
- `user_id`
- `user_user`
- `user_name`
- `user_email`
- `user_last_login`
- `user_created`
- `user_image_count`
- `right_id`
- `right_title`
- `right_uri`
- `right_image`
- `right_description`
- `right_description_raw`
- `right_markup`
- `right_modified`
- `right_modified_format`
- `right_image_count`

EXIF values vary by image (use **Show EXIF Data** to reveal your options), but can be inserted like so:

- `image_exif_model` (e.g., Nikon D200)
- `image_exif_software` (e.g., Adobe Imageshop CS5 Macintosh)
- And so on...

###### Post

- `post_id`
- `post_title`
- `post_title_url`
- `post_text`
- `post_text_raw`
- `post_markup`
- `post_images`
- `post_views`
- `post_words`
- `post_created`
- `post_created_format`
- `post_published`
- `post_published_format`
- `post_modified`
- `post_modified_format`
- `post_comment_count`
- `post_comment_disabled`

###### Set

- `set_id`
- `set_title`
- `set_title_url`
- `set_type`
- `set_description`
- `set_description_raw`
- `set_markup`
- `set_images`
- `set_views`
- `set_image_count`
- `set_call`
- `set_modified`
- `set_modified_format`
- `set_created`
- `set_created_format`

###### Size

- `size_id`
- `size_src`
- `size_height`
- `size_width`
- `size_modified`

###### Tag

- `tag_id`
- `tag_name`