var editor;
editor = CKEDITOR.replace( '<?=$_GET['class']?>' , {
<? if($_GET['type']=='basic'){ ?>
	toolbar :
	[
	['Source','-','Preview'],
	['Cut','Copy','Paste','PasteText','PasteFromWord'],
	['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
	['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
	['NumberedList','BulletedList','-','Outdent','Indent','Blockquote','CreateDiv'],
	['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
	['Link','Unlink','Anchor','HorizontalRule','Smiley','SpecialChar','Maximize'],

	],
<? }?>
extraPlugins: 'imageuploader,html5video,video,widget',
filebrowserImageBrowseUrl: '/editor/e_modules/ckeditor2/plugins/imageuploader/main.php',
filebrowserBrowseUrl: '/editor/e_modules/ckeditor2/plugins/imageuploader/main.php',
filebrowserUploadUrl: '/editor/e_modules/ckeditor2/plugins/imageuploader/imgupload.php',
filebrowserImageUploadUrl: '/editor/e_modules/ckeditor2/plugins/imageuploader/imgupload.php',
height: '<?=$_GET['height']?>px'
});