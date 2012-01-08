##
# GNU Makefile for Pork project.
#
# @author Rafał Wrzeszcz <rafal.wrzeszcz@wrzasq.pl>
# @copyright 2012 (C) by Rafał Wrzeszcz - Wrzasq.pl.
# @version 0.0.1
# @since 0.0.1
# @package Pork
##

# default task
default: check

# full task 
all: init check

# initializes project directory - mainly fetches dependencies
init:
	git submodule update --init --recursive

# checks files syntax
check:
	find . -name "*.php" -exec php -l {} \;
