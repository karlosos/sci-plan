#!/bin/bash
php ${OPENSHIFT_REPO_DIR}www/parseData.php
date >> ${OPENSHIFT_DATA_DIR}ticktock.log
