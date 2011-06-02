import java.io.*;
import java.lang.reflect.*;

/** This class will set I/O fields and run main method from the class.
  * Throws IllegalArgumentException if "main" can not be found. 
  */
public class TextIOTester {
    private String [] mainArgs = { };
    private ByteArrayOutputStream os;
    private ByteArrayInputStream is;
    private String inputText = "";
    private String [] outLines;
    Class<?> mainClass;

    /** Create New TextIOTester instance. */
    TextIOTester(Class <?> mainClass) {
        this.mainClass = mainClass;

        initStreams();
    }

    private void initStreams() {
        os = new ByteArrayOutputStream();
        is = new ByteArrayInputStream(inputText.getBytes());

        applyStreams();
    }

    private void initInputStream() {
        is = new ByteArrayInputStream(inputText.getBytes());

        applyStreams();
    }

    private void applyStreams() {
        setTextIOStreams();
        setAnswerIOStreams();
    }

    /** Point TextIO class streams to our buffers */
    private void setTextIOStreams() {
        TextIO.writeStream(os);
        TextIO.readStream(is);
    }

    /** Point Answer class I/O Fields to our buffers (if they exist) */
    private void setAnswerIOStreams() {
        try {
            Field f = mainClass.getDeclaredField("out");
            f.set(null, new PrintStream(os));
        } catch(Exception e) { };

        try {
            Field f = mainClass.getDeclaredField("in");
            f.set(null, is);
        } catch(Exception e) { };
    }

    /** Run "main" method in answer class.
     *  Will throw IllegalArgumentException if main(String []) can not be found */
    public void runMain() {
        try {
            Class [] at = { String[].class };
            Object [] a = { mainArgs };
            Method m = mainClass.getMethod("main", at);
            m.invoke(null, a);
        } catch(Exception e) {
            throw new IllegalArgumentException("Unable to run main: " + e);
        }
    }

    /** Get output as a long String */
    public String getOutput() {
        return os.toString();
    }

    /** Get output as an array of lines */
    public String [] getOutputLines() {
        return os.toString().split("\n");
    }

    /** Set input stream contents. */
    public void setInput(String input) {
        this.inputText = input;
        initInputStream();
    }

    /** Set input stream contents. */
    public void setInput(String [] inputLines) {
        this.inputText = "";
        for(int i = 0; i < inputLines.length; i++) {
            inputText += inputLines[i];
            inputText += "\n";
        }
        initInputStream();
    }

    /** Convenience method that will init I/O streams and run "main".
     *  Throws IllegalArgumentException if main(String []) can not be found */
    public static String [] runMainWithInput(String input, Class <?> mainClass) {
        TextIOTester t = new TextIOTester(mainClass);
        t.setInput(input);
	t.runMain();
        return t.getOutputLines();
    }

    /** Convenience method that will init I/O streams and run "main".
     *  Throws IllegalArgumentException if main(String []) can not be found */
    public static String [] runMain(Class <?> mainClass) {
        TextIOTester t = new TextIOTester(mainClass);
	t.runMain();
        return t.getOutputLines();
    }
}
