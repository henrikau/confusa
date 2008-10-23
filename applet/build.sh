#!/bin/bash
JAR_TARGET=confusa.jar

function build  {
    if [ ! -d bin ]; then
	mkdir bin/
    fi
    cd src/
    javac -classpath confusa/ -extdirs ../lib/bcprov-jdk16-141.jar -d ../bin/  confusa/*.java
    if [ ! $? -eq 0 ]; then
	echo "errors in build"
	exit
    fi
    cd ../
}

function make_jar {
    build

    # create jar
    cd bin/
    jar -cfvm ../$JAR_TARGET ../confusa.manifest confusa/
    cd ../
}
if [ ! -d bin ]; then
    mkdir bin/
fi
cd src/
javac -classpath confusa/ -extdirs ../lib/ confusa/*.java -d ../bin/
if [ ! $? -eq 0 ]; then
    echo "errors in build"
    exit
fi
cd ../


# make jar
rm -f $JAR_TARGET
cd bin/
# jar -cfvm ../$JAR_TARGET ../confusa.manifest confusa/
# META-INF/MANIFEST.MF
jar cvfm ../$JAR_TARGET ../confusa.manifest *


