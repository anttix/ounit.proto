#!/bin/sh

JAVAC=javac
JAVA=java
JUNIT=/usr/share/java/junit4.jar
OUNIT=/srv/ounit/Engines/JUnit
POLICY=/srv/ounit/junit.policy

$JAVAC -cp $JUNIT:$OUNIT:. AnswerTest.java && \
$JAVA -cp $JUNIT:$OUNIT:. -Djava.security.manager -Djava.security.policy=$POLICY \
     org.junit.runner.JUnitCore AnswerTest 2>&1 \
                    | grep -v '^[ 	]*at'

rm -f Answer.class
[ -f AnswerStub.java ] && ln -sf AnswerStub.java Answer.java
