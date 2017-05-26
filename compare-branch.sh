#! /bin/sh

READLINK_COMMAND="readlink";

if hash greadlink 2>/dev/null; then
  READLINK_COMMAND="greadlink";
fi

echo "USING $READLINK_COMMAND" 1>&2;

BRANCHNAME=$1;

ROOT_DIR=`$READLINK_COMMAND -f .`;
ANALYSIS_DIR=`$READLINK_COMMAND -f ./analysis`;
OUTPUT_DIR=$ANALYSIS_DIR/json
OUTPUT_MASTER_DIR=$ANALYSIS_DIR/json/master
OUTPUT_BRANCH_DIR=$ANALYSIS_DIR/json/branch

MASTER_ANALYZER_COMMAND="php $ROOT_DIR/analyzer.php --outputdir=$OUTPUT_MASTER_DIR"
BRANCH_ANALYZER_COMMAND="php $ROOT_DIR/analyzer.php --outputdir=$OUTPUT_BRANCH_DIR"
COMPARER_COMMAND="php $ROOT_DIR/comparer.php --masterdir=$OUTPUT_MASTER_DIR --branchdir=$OUTPUT_BRANCH_DIR"

#ANALYSIS_COMMAND="find $ANALYSIS_DIR/portal/www/roadrunner -path \"$ANALYSIS_DIR/portal/www/roadrunner/apps/libraries\" -prune -o -path \"$ANALYSIS_DIR/portal/www/roadrunner/vendor\" -prune -o -path \"$ANALYSIS_DIR/portal/www/roadrunner/core/libraries\" -prune -o -type f -name '*.php' -print | $ANALYZER_COMMAND"

echo "Root directory: $ROOT_DIR" 1>&2;
echo "Analysis directory: $ANALYSIS_DIR" 1>&2;
echo "Comparer command: $COMPARER_COMMAND" 1>&2;
echo "Analysis command: $ANALYSIS_COMMAND" 1>&2;

# initialize analysis dir and clone from git
mkdir -p $ANALYSIS_DIR 1>&2;
mkdir -p $OUTPUT_DIR 1>&2;
mkdir -p $OUTPUT_MASTER_DIR 1>&2;
mkdir -p $OUTPUT_BRANCH_DIR 1>&2;

cd $ANALYSIS_DIR;
git clone git@github.com:Shapeways/portal.git 1>&2;
cd portal;
git checkout master 1>&2;
git pull origin master 1>&2;

# analyze master
cd $ROOT_DIR;
find $ANALYSIS_DIR/portal/www/roadrunner -path "$ANALYSIS_DIR/portal/www/roadrunner/apps/libraries" -prune -o -path "$ANALYSIS_DIR/portal/www/roadrunner/vendor" -prune -o -path "$ANALYSIS_DIR/portal/www/roadrunner/core/libraries" -prune -o -type f -name '*.php' -print | $MASTER_ANALYZER_COMMAND

# switch to new branch
cd $ANALYSIS_DIR/portal;
git fetch 1>&2;
git checkout $BRANCHNAME 1>&2;
git pull origin $BRANCHNAME 1>&2;
git branch 1>&2;

# Analyze branch
cd $ROOT_DIR;
find $ANALYSIS_DIR/portal/www/roadrunner -path "$ANALYSIS_DIR/portal/www/roadrunner/apps/libraries" -prune -o -path "$ANALYSIS_DIR/portal/www/roadrunner/vendor" -prune -o -path "$ANALYSIS_DIR/portal/www/roadrunner/core/libraries" -prune -o -type f -name '*.php' -print | $BRANCH_ANALYZER_COMMAND

# Compare
cd $ROOT_DIR;
$COMPARER_COMMAND;

# cleanup and exit
cd $ROOT_DIR;
#rm -rf $ANALYSIS_DIR