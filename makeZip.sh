#!/bin/bash

for i in sofort mrcash creditcard paysafecard ideal; do 

path0="./upload/admin/controller/extension/payment/$i.php";
path1="./upload/admin/model/extension/payment/$i.php"; 
path2="./upload/catalog/controller/extension/payment/$i.php";
path3="./upload/catalog/model/extension/payment/$i.php";
#view
path4="./upload/catalog/view/theme/default/template/extension/payment/$i.tpl";
path5="./upload/admin/view/template/extension/payment/$i.tpl";
#lang 
path6="./upload/admin/language/nl-nl/extension/payment/$i.php";
path7="./upload/admin/language/en-gb/extension/payment/$i.php";
path8="./upload/catalog/language/nl-nl/extension/payment/$i.php";
path9="./upload/catalog/language/en-gb/extension/payment/$i.php";



zip -r opencart_2.4.zip $path0 $path1 $path2 $path3 $path4 $path5 $path6 $path7 $path8 $path9

done

#zip controller and target core

path=upload/catalog/controller/extension/payment/tp_callback.php
path2=upload/system/helper/targetpay.class.php

zip -r opencart_2.4.zip $path $path2
