#!/bin/bash
php ${OPENSHIFT_REPO_DIR}www/process.php
date >> ${OPENSHIFT_DATA_DIR}ticktock.log
