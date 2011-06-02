public class Answer {
    static final int FLOOR = 1;
    static final int ROOF = 10;

    public static void main(String [] args) {
        int n;
        do {
            TextIO.put("Enter a number " + FLOOR + " - " + ROOF + ": ");
            n = TextIO.getlnInt();
        } while(n < FLOOR || n > ROOF);
        TextIO.putln("\nYour lucky number is " + n);
    }
}
