// Arduino command handling update
// Define your actual beacon IDs here (adjust to match your system)
const int knownIds[] = {1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15};
const int numKnownIds = sizeof(knownIds) / sizeof(knownIds[0]);

// --- Periodic "online" message variables ---
unsigned long lastOnlineTime = 0;
const unsigned long onlineInterval = 20000; // 10 seconds

// --- Periodic "error" message variables ---
unsigned long lastErrorTime = 0;
const unsigned long errorInterval = 20000; // 10 seconds 300000

void setup()
{
  Serial.begin(9600);
  while (!Serial)
    ;
  delay(200);
  Serial.println("Arduino Ready");

  lastOnlineTime = millis();
  lastErrorTime = millis();
}

void loop()
{
  if (Serial.available())
  {
    String cmd = Serial.readStringUntil('\n');
    cmd.trim();
    if (cmd.length() == 0)
      return;

    // Special greeting
    if (cmd.equalsIgnoreCase("HI ARDUINO"))
    {
      Serial.println("HELLO SYSTEM TEST TESTTT");
      return;
    }

    // Parse command if it follows the <...> format
    if (cmd.startsWith("<") && cmd.endsWith(">"))
    {
      String inner = cmd.substring(1, cmd.length() - 1);
      inner.trim();
      processCommand(inner);
    }
    // ignore other lines
  }

  // --- Send "ID:3 Online" every 10 seconds ---
  unsigned long currentMillis = millis();
  if (currentMillis - lastOnlineTime >= onlineInterval)
  {
    lastOnlineTime = currentMillis;
    Serial.println("ID:2 Online");
  }

  // --- Send "ID:2 ERROR: 0x01" every 10 seconds ---
  if (currentMillis - lastErrorTime >= errorInterval)
  {
    lastErrorTime = currentMillis;
    Serial.println("ID:2 ERROR: 0x01");
  }
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
    // If com is empty, we do nothing (or could treat as error, but we'll ignore)

#ifdef DEBUG_ECHO
    char buffer[120];
    snprintf(buffer, sizeof(buffer),
             "<id=%d;colorA=%s;delayA=%s;colorB=%s;delayB=%s>",
             id, colorA.c_str(), delayA.c_str(), colorB.c_str(), delayB.c_str());
    Serial.print("   → Sent: ");
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

// Arduino command handling update
// Define your actual beacon IDs here (adjust to match your system)
const int knownIds[] = {1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15};
const int numKnownIds = sizeof(knownIds) / sizeof(knownIds[0]);

// --- Periodic "online" message variables ---
unsigned long lastOnlineTime = 0;
const unsigned long onlineInterval = 20000; // 10 seconds

// --- Periodic "error" message variables ---
unsigned long lastErrorTime = 0;
const unsigned long errorInterval = 20000; // 10 seconds 300000

void setup()
{
  Serial.begin(9600);
  while (!Serial)
    ;
  delay(200);
  Serial.println("Arduino Ready");

  lastOnlineTime = millis();
  lastErrorTime = millis();
}

void loop()
{
  if (Serial.available())
  {
    String cmd = Serial.readStringUntil('\n');
    cmd.trim();
    if (cmd.length() == 0)
      return;

    // Special greeting
    if (cmd.equalsIgnoreCase("HI ARDUINO"))
    {
      Serial.println("HELLO SYSTEM TEST TESTTT");
      return;
    }

    // Parse command if it follows the <...> format
    if (cmd.startsWith("<") && cmd.endsWith(">"))
    {
      String inner = cmd.substring(1, cmd.length() - 1);
      inner.trim();
      processCommand(inner);
    }
    // ignore other lines
  }

  // --- Send "ID:3 Online" every 10 seconds ---
  unsigned long currentMillis = millis();
  if (currentMillis - lastOnlineTime >= onlineInterval)
  {
    lastOnlineTime = currentMillis;
    Serial.println("ID:2 Online");
  }

  // --- Send "ID:2 ERROR: 0x01" every 10 seconds ---
  if (currentMillis - lastErrorTime >= errorInterval)
  {
    lastErrorTime = currentMillis;
    Serial.println("ID:2 ERROR: 0x01");
  }
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
    // If com is empty, we do nothing (or could treat as error, but we'll ignore)

#ifdef DEBUG_ECHO
    char buffer[120];
    snprintf(buffer, sizeof(buffer),
             "<id=%d;colorA=%s;delayA=%s;colorB=%s;delayB=%s>",
             id, colorA.c_str(), delayA.c_str(), colorB.c_str(), delayB.c_str());
    Serial.print("   → Sent: ");
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