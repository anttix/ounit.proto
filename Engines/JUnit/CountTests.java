import java.lang.reflect.*;
import org.junit.*;

public class CountTests {
   public static void main(String[] args) throws Exception {
      int count = 0;
      for (Method m : Class.forName(args[0]).getMethods()) {
         if (m.isAnnotationPresent(Test.class)) {
            count++;
         }
      }
      System.out.printf("%d%n", count);
   }
}
