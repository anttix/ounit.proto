#!/bin/sh


JAVAC=/home/users/anttix/qe.anttix.org/jdk/bin/javac
JAVA=/home/users/anttix/qe.anttix.org/jdk/bin/java
JUNIT=/home/users/anttix/qe.anttix.org/junit4.jar
POLICY=/home/users/anttix/qe.anttix.org/ounit/junit.policy

$JAVAC -cp $JUNIT:. AnswerTest.java && \
$JAVA -cp $JUNIT:. -Djava.security.manager -Djava.security.policy=$POLICY \
     org.junit.runner.JUnitCore AnswerTest 2>&1 \
                    | grep -v '^[ 	]*at'

rm -f Answer.class
