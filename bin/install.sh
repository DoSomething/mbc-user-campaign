#!/bin/bash
##
# Installation script for mbc-userAPI-campaignActivity
##

# Assume messagebroker-config repo is one directory up
cd ../messagebroker-config

# Gather path from root
MBCONFIG=`pwd`

# Back tombc-userAPI-campaignActivity
cd ../mbc-userAPI-campaignActivity

# Create SymLink for mbc-userAPI-campaignActivity application to make reference to
# for all Message Broker configuration settings
ln -s $MBCONFIG .
