import face_recognition
import json
import os
import mysql.connector


db_config = {
    'host': 'localhost',
    'user': 'root',        # ← your MySQL username
    'password': '',        # ← your MySQL password
    'database': 'freelancing'  # ← your database name
}
def get_all_students():
    try:
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT student_id, name, profile_image FROM students WHERE profile_image IS NOT NULL AND profile_image != ''")
        students = cursor.fetchall()
        cursor.close()
        conn.close()
        return students
    except Exception as e:
        print(f"Database error: {e}")
        return []

def generate_embeddings():
    students = get_all_students()
    if not students:
        print("No students with profile images found.")
        return
    embeddings_data = {}
    uploads_dir = os.path.join(os.path.dirname(__file__), 'admin', 'uploads')
    for student in students:
        image_path = os.path.join(uploads_dir, student['profile_image'])
        if not os.path.exists(image_path):
            print(f"Image not found: {image_path}")
            continue
        image = face_recognition.load_image_file(image_path)
        encodings = face_recognition.face_encodings(image)
        if len(encodings) == 0:
            print(f"No face detected in {student['name']} (ID: {student['student_id']})")
            continue
        embeddings_data[student['student_id']] = {
            'name': student['name'],
            'embedding': encodings[0].tolist()
        }
        print(f"✓ Processed {student['name']} (ID: {student['student_id']})")
    with open('face_embeddings.json', 'w') as f:
        json.dump(embeddings_data, f, indent=2)
    print(f"\n✅ Saved embeddings for {len(embeddings_data)} students.")

if __name__ == '__main__':
    generate_embeddings()