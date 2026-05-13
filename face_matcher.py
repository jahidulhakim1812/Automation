import face_recognition
import json
import sys
import numpy as np
import os

EMBEDDINGS_FILE = 'face_embeddings.json'
SIMILARITY_THRESHOLD = 0.5

def load_embeddings():
    if not os.path.exists(EMBEDDINGS_FILE):
        return {}
    with open(EMBEDDINGS_FILE, 'r') as f:
        data = json.load(f)
    for student_id, info in data.items():
        info['embedding'] = np.array(info['embedding'])
    return data

def find_best_match(query_encoding, embeddings_data):
    if not embeddings_data:
        return None, 1.0
    best_id = None
    best_distance = 1.0
    for student_id, info in embeddings_data.items():
        stored_encoding = info['embedding']
        distance = np.linalg.norm(query_encoding - stored_encoding)
        if distance < best_distance:
            best_distance = distance
            best_id = student_id
    similarity = 1 - (best_distance / 2) if best_distance <= 2 else 0
    if best_distance < SIMILARITY_THRESHOLD:
        return best_id, similarity
    return None, similarity

def process_image_file(image_path):
    try:
        if not os.path.exists(image_path):
            print(f"Error: image file not found: {image_path}", file=sys.stderr)
            return None, 0.0

        image = face_recognition.load_image_file(image_path)
        encodings = face_recognition.face_encodings(image)

        if not encodings:
            return None, 0.0

        query_encoding = encodings[0]
        embeddings = load_embeddings()
        best_id, similarity = find_best_match(query_encoding, embeddings)
        return best_id, similarity

    except Exception as e:
        print(f"Error: {e}", file=sys.stderr)
        return None, 0.0

if __name__ == '__main__':
    if len(sys.argv) < 2:
        print("NO_MATCH")
        sys.exit(1)

    image_path = sys.argv[1]
    best_id, similarity = process_image_file(image_path)

    if best_id:
        print(f"MATCH:{best_id}:{similarity:.2f}")
    else:
        print("NO_MATCH")