#!/bin/bash
JAR_TARGET=confusa.jar

function build  {
    if [ ! -d bin ]; then
	mkdir bin/
    fi
    cd src/
    javac -classpath confusa/ -d ../bin/  confusa/*.java
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
javac -classpath confusa/ -d ../bin/  confusa/*.java
if [ ! $? -eq 0 ]; then
    echo "errors in build"
    exit
fi
cd ../

cd bin/
jar -cfvm ../$JAR_TARGET ../confusa.manifest confusa/



