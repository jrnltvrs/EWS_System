/*
 * Mega 2560 RX/TX request/reply firmware (ESP is not used).
 * Arduino C++: copy this file into an Arduino sketch named controller.ino.
 * Serial = USB serial / RX0 pin 0 and TX0 pin 1, 9600 baud, 8N1.
 * These are the same UART: use only one connected sender at a time.
 *
 * Host contract: send one newline-terminated command, then wait for the
 * entire reply before sending another. ID lists/all produce one line per ID.
 * This is software half-duplex, not a single-wire or RS-485 interface.
 * Existing beacon handlers below are simulations, not hardware control.
 */
#include <Arduino.h>

const int knownIds[] = {1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15};
const int numKnownIds = sizeof(knownIds) / sizeof(knownIds[0]);
const size_t RX_CAPACITY = 256;
char rxBuffer[RX_CAPACITY];
size_t rxLength = 0;
bool rxOverflow = false;

String getValue(const String &data, const String &key);
void processCommand(const String &inner);
void handleLine();

void setup()
{
  Serial.begin(9600);
  // Stay silent until requested: no boot message or periodic transmissions.
}

void loop()
{
  while (Serial.available() > 0)
  {
    const char incoming = (char)Serial.read();
    if (incoming == '\n')
    {
      // A full request has arrived. Finish TX before processing another RX.
      if (rxOverflow)
      {
        Serial.println("ERROR: command too long");
      }
      else
      {
        rxBuffer[rxLength] = '\0';
        handleLine();
      }
      Serial.flush(); // Wait until the final outgoing byte has been sent.
      rxLength = 0;
      rxOverflow = false;
      return;
    }
    if (incoming == '\r')
      continue; // Accept both LF and CRLF.
    if (rxOverflow)
      continue; // Discard oversized line through LF.
    if (rxLength < RX_CAPACITY - 1)
    {
      rxBuffer[rxLength++] = incoming;
    }
    else
    {
      rxOverflow = true;
    }
  }
}

void handleLine()
{
  String cmd(rxBuffer);
  cmd.trim();
  if (cmd.length() == 0)
    return;
  if (cmd.equalsIgnoreCase("HI ARDUINO"))
  {
    Serial.println("HELLO SYSTEM TEST TESTTT");
    return;
  }
  if (!cmd.startsWith("<") || !cmd.endsWith(">"))
  {
    Serial.println("ERROR: expected <id=...;com=...>");
    return;
  }
  String inner = cmd.substring(1, cmd.length() - 1);
  inner.trim();
  if (getValue(inner, "id").length() == 0)
  {
    Serial.println("ERROR: missing id");
    return;
  }
  String ids = getValue(inner, "id");
  if (!ids.equalsIgnoreCase("all"))
  {
    int start = 0;
    do
    {
      int comma = ids.indexOf(',', start);
      String token = comma < 0 ? ids.substring(start) : ids.substring(start, comma);
      token.trim();
      bool valid = token.length() > 0 && token.length() <= 2;
      for (unsigned int i = 0; i < token.length(); ++i)
      {
        if (token[i] < '0' || token[i] > '9')
          valid = false;
      }
      int id = token.toInt();
      if (!valid || id < 1 || id > 15)
      {
        Serial.println("ERROR: id must be 1-15 or all");
        return;
      }
      if (comma < 0)
        break;
      start = comma + 1;
    } while (true);
  }
  processCommand(inner);
}
// Extract value for a given key from a string like "key=value;key2=value2;..."
String getValue(const String &data, const String &key)
{
  int start = 0;
  int semicolon;
  while ((semicolon = data.indexOf(';', start)) != -1)
  {
    String pair = data.substring(start, semicolon);
    int eq = pair.indexOf('=');
    if (eq != -1)
    {
      String k = pair.substring(0, eq);
      k.trim();
      if (k.equalsIgnoreCase(key))
      {
        String v = pair.substring(eq + 1);
        v.trim();
        return v;
      }
    }
    start = semicolon + 1;
  }
  // Last part (no trailing semicolon)
  String lastPair = data.substring(start);
  int eq = lastPair.indexOf('=');
  if (eq != -1)
  {
    String k = lastPair.substring(0, eq);
    k.trim();
    if (k.equalsIgnoreCase(key))
    {
      String v = lastPair.substring(eq + 1);
      v.trim();
      return v;
    }
  }
  return ""; // key not found
}

void processCommand(const String &inner)
{
  // Extract all fields
  String idStr = getValue(inner, "id");
  String com = getValue(inner, "com");
  String colorA = getValue(inner, "colorA");
  String delayA = getValue(inner, "delayA");
  String colorB = getValue(inner, "colorB");
  String delayB = getValue(inner, "delayB");

  if (idStr.length() == 0)
    return;

  // Helper to send response to a single ID based on com value
  auto sendToId = [&](int id)
  {
    if (com.equalsIgnoreCase("serv"))
    {
      Serial.print("ID:");
      Serial.print(id);
      Serial.println(" Service mode activated.");
    }
    else if (com.equalsIgnoreCase("reset"))
    {
      Serial.print("ID:");
      Serial.print(id);
      Serial.println(" System is Restarting...");
    }
    else if (com.equalsIgnoreCase("status"))
    {
      Serial.print("ID:");
      Serial.print(id);
      Serial.println(" Online");
    }
    else if (com.equalsIgnoreCase("vv"))
    {
      // Dummy voltage value
      Serial.print("ID:");
      Serial.print(id);
      Serial.println(" Measured Voltage: 13.02 V");
    }
    else if (com.length() > 0)
    {
      // Unknown command
      Serial.print("ID:");
      Serial.print(id);
      Serial.println(" command does not exist");
    }
    else
    {
      // Acknowledge reception only; lamp output hardware is not implemented.
      Serial.print("ID:");
      Serial.print(id);
      Serial.println(" Received (simulation only)");
    }

#ifdef DEBUG_ECHO
    char buffer[120];
    snprintf(buffer, sizeof(buffer),
             "<id=%d;colorA=%s;delayA=%s;colorB=%s;delayB=%s>",
             id, colorA.c_str(), delayA.c_str(), colorB.c_str(), delayB.c_str());
    Serial.print("   â†’ Sent: ");
    Serial.println(buffer);
#endif
  };

  if (idStr.equalsIgnoreCase("all"))
  {
    for (int i = 0; i < numKnownIds; i++)
    {
      sendToId(knownIds[i]);
    }
  }
  else
  {
    // Comma-separated list
    int start = 0;
    int comma;
    while ((comma = idStr.indexOf(',', start)) != -1)
    {
      String idToken = idStr.substring(start, comma);
      idToken.trim();
      int id = idToken.toInt();
      if (id > 0)
      {
        sendToId(id);
      }
      start = comma + 1;
    }
    // Last ID
    String lastId = idStr.substring(start);
    lastId.trim();
    int id = lastId.toInt();
    if (id > 0)
    {
      sendToId(id);
    }
  }
}
