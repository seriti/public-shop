<?php
/*
NB: This is not stand alone code and is intended to be used within "seriti/slim3-skeleton" framework
The code snippet below is for use within an existing src/setup_app.php file within this framework
add the below code snippet to the end of existing "src/setup_app.php" file.
This tells the framework about module: name, sub-memnu route list and title, database table prefix.
*/

$container['config']->set('module','shop',['name'=>'Shop manager',
                                            'route_root'=>'admin/shop/',
                                            'route_list'=>['dashboard'=>'Dashboard','product'=>'Products','category'=>'Categories',
                                                           'order'=>'Orders','payment'=>'Payments','task'=>'Tasks','report'=>'Reports'],
                                            'labels'=>['category'=>'Category','type'=>'Type','order'=>'Order'],
                                            'images'=>['access'=>'PUBLIC'],
                                            'table_prefix'=>'shp_'
                                            ]);
