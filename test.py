import mysql.connector

conn = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="searchar"
)

if conn.is_connected():
    print("MySQL Connected OK!")

cursor = conn.cursor()
cursor.execute("SHOW TABLES")
tables = cursor.fetchall()
print("Tables found:")
for t in tables:
    print(" -", t[0])

conn.close()