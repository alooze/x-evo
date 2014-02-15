<?php
$path = 'assets/extensions/Xadmin/'
$headerBlock = <<<HDR
    <link rel="stylesheet" type="text/css" href="/themes/default/easyui.css">
    <link rel="stylesheet" type="text/css" href="../../themes/icon.css">
    <link rel="stylesheet" type="text/css" href="../demo.css">
    <script type="text/javascript" src="../../jquery.min.js"></script>
    <script type="text/javascript" src="../../jquery.easyui.min.js"></script>
HDR;
$modx->regClientStartupHTMLBlock($headerBlock);