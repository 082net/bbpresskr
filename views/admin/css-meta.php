<?php
/**
 * CSS Style for admin pages
 * 
 * @package bbPressKR
 * @subpackage Admin
 * @copyright 2014-2015 082NeT(082net@gmail.com)
 */
?>
<style type="text/css">

#bbpmetafields .inside textarea,
#bbpmetafields .inside input,
#bbpmetafields .inside select,
#bbppermission .inside textarea,
#bbppermission .inside input,
#bbppermission .inside select {
	font-size: 12px;
}


/*------------------------------------------------------------------------------
  11.1 - Custom Fields: from edit.css;
------------------------------------------------------------------------------*/

#bbpmetafields thead th {
	padding: 5px 8px 8px;
	background-color: #f1f1f1;
}

#bbpmetafields .submit {
	border: 0 none;
	float: none;
	padding: 0 8px 8px;
}

#side-sortables #bbpmetafields .submit {
	margin: 0;
	padding: 0;
}

#side-sortables #bbpmetafields #the-list textarea {
	height: 85px;
}

#side-sortables #bbpmetafields td.left input,
#side-sortables #bbpmetafields td.left select,
#side-sortables #bbpmetafields #newmetaleft a {
	margin: 3px 3px 0;
}

#bbpmetafields table {
	margin: 0;
	width: 100%;
	border: 1px solid #dfdfdf;
	border-spacing: 0;
	/*background-color: #f9f9f9;*/
}

#bbpmetafields tr {
	vertical-align: top;
}

#bbpmetafields table input,
#bbpmetafields table select,
#bbpmetafields table textarea {
	width: 96%;
	margin: 8px;
}

#side-sortables #bbpmetafields table input,
#side-sortables #bbpmetafields table select,
#side-sortables #bbpmetafields table textarea {
	margin: 3px;
}

#bbpmetafields th.left,
#bbpmetafields td.left {
	width: 38%;
}

#bbpmetafields .submit input {
	margin: 0;
	width: auto;
}

#bbpmetafields #newmetaleft a {
	display: inline-block;
	margin: 0 8px 8px;
	text-decoration: none;
}

.no-js #bbpmetafields #enternew {
	display: none;
}

#post-body-content .compat-attachment-fields {
	margin-bottom: 20px;
}

#bbpmetafields tr.alternate td {
	/*background-color: white;*/
}

#bbpmetafields span.bbpmeta-order {
	line-height: 3em;
	padding: 0 4px;
}

</style>