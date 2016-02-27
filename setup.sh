#!/bin/bash 


#git clone  'https://github.com/truongan/Sharif-Judge' . 
usage(){
cat << EOF
usage: $0 [-i install_dir] [-o public_dir] -u db_user -p db_password base_url 

base url will be set in config.php

OPTIONS:
	-h show this message
	-i install directory, default to current working directory
	-o public directory to put index.php, default to be the public 
	   directory in the same directory as install directory
	-u database username
	-p database password
	-d database name
EOF
}

install="`pwd`"
public=''
db_user=''
db=''
db_password=''
base_url=''

while getopts "hi:o:u:p:d:" ops ; do
	case "${ops}" in 
		h)	usage ;;
		i)	install=${OPTARG};;
		o)	public=${OPTARg};;
		u)	db_user=${OPTARG};;
		p)	db_password=${OPTARG};;
		d)	db=${OPTARG};;
		*)	usage; exit 1;;
	esac
done
shift $((OPTIND-1))

base_url=$1

if [ "$db_user" = "" ]; then
	usage; exit 1
fi

if [ "$public" = "" ]; then 
	public="$install/../public_html"
fi

if [ "$db" = '' ]; then
	db="$db_user"
fi

cat << EOF 
"install=$install"
"public=$public"
"db_user=$db_user"
"db=$db"
"db_password=$db_password"
"base_url=$base_url"
EOF

cd $install
git clone  'https://github.com/truongan/Sharif-Judge' . 
git checkout wecode

cd $public
rm index.html index.php
ln -s $install/index.php $install/assets $install/.htaccess .
echo sed -i "s@system_path = 'system'@system_path = '$install/system'@g" index.php
sed -i "s@system_path = 'system'@system_path = '$install/system'@g" index.php
sed -i "s@application_folder = 'application'@application_folder = '$install/application'@g" index.php

cd $install/application/config
cp config.php.example config.php
cp database.php.example database.php 

echo sed -i "s@base_url'] = ''@base_url'] = '$base_url'@g" config.php
sed -i "s@base_url'] = ''@base_url'] = '$base_url'@g" config.php
sed -i "s@index_page'] = 'index.php'@index_page'] = ''@g" config.php

sed -i "s/homestead/$db_user/g" database.php
sed -i "s/secret/$db_password/g" database.php
sed -i "s/sharif/$db/g" database.php
