import org.junit.*;
import static org.junit.Assert.*;
import java.io.*;

public class AnswerTest {
    static final int FLOOR = 1;
    static final int ROOF = 10;

    public void runWithNumbers(int [] in) {
        // FIXME: We blindly assume that last number is the only one in range
        int n = in[in.length - 1];
        String ans = "Your lucky number is " + n;
        String input = "";
        for(int i: in) input += i + "\n";
        String [] outLines = TextIOTester.runMainWithInput(input, Answer.class);
        assertFalse("No lines in output", outLines.length < 1);
        assertEquals(ans, outLines[outLines.length - 1]);
    }

    @Test (timeout=1000)
    public void testWithCorrectNumber() {
        int l = (int)(Math.random() * (ROOF - FLOOR + 1) + FLOOR);
        int [] n = { l };
        runWithNumbers(n);
    }

    @Test (timeout=1000)
    public void testWithOneWrongNumber() {
        int l = (int)(Math.random() * (ROOF - FLOOR + 1) + FLOOR);
        int [] n = { ROOF + 1, l };
        runWithNumbers(n);
    }

    @Test (timeout=1000)
    public void testWithTenWrongNumbers() {
        int l = (int)(Math.random() * (ROOF - FLOOR + 1) + FLOOR);
        int [] n = new int[10];
        for(int i = 0; i < n.length - 1; i++) {
            n[i] = (i % 2 == 0) ? FLOOR - 1 : ROOF + 1;
        }
        n[9] = l;
        runWithNumbers(n);
    } 
}
