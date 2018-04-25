<?php

use Malenki\Argile\Options as Options;

$opt = Options::getInstance();

$opt->addGroup('mysql', 'MySQL connections parans');

$opt->newValue('user', 'mysql')
	->required()
	->short('u')
	->long('user')
	->help('Required a mysql user.')
	;
$opt->newValue('password', 'mysql')
	->required()
	->short('p')
	->long('password')
	->help('Required a mysql password.')
	;
$opt->newValue('name', 'mysql')
	->required()
	->short('n')
	->long('name')
	->help('Required a mysql database name.')
	;

$opt->newValue('host', 'mysql')
	->required()
	->short('h')
	->long('host')
	->help('Required a mysql host.')
	;
$opt->newValue('siteid', 'mysql')
	->required()
	->short('id')
	->long('siteid')
	->help('Required a siteid to extract.')
	;
$opt->newValue('prefix', 'mysql')
	->required()
	->short('prx')
	->long('prefix')
	->help('Required the prefix used on database.')
	;
$opt->newValue('wprootpath', 'mysql')
	->required()
	->short('wpr')
	->long('wprootpath')
	->help('Required the wprootpath (WP ROOT PATH INSTALLATION)')
	;
$opt->parse();

if( ! $opt->has('user')  ):
	echo "missing user param\n";
	exit;
endif;

if( ! $opt->has('password')  ):
	echo "missing password param\n";
	exit;
endif;

if( ! $opt->has('name')  ):
	echo "missing name param\n";
	exit;
endif;

if( ! $opt->has('host')  ):
	echo "missing host param\n";
	exit;
endif;
if( ! $opt->has('siteid')  ):
	echo "missing siteid param\n";
	exit;
endif;
if( ! $opt->has('prefix')  ):
	echo "missing prefix param\n";
	exit;
endif;
if( ! $opt->has('wprootpath')  ):
	echo "missing wprootpath param\n";
	exit;
endif;