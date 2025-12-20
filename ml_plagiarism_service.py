from flask import Flask, request, jsonify
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.svm import SVC
import joblib

app = Flask(__name__)

# Load or train model
# For a real system, train once offline and save with joblib.
# Here is a tiny dummy training just as a placeholder.
try:
    vectorizer = joblib.load("vectorizer.pkl")
    model = joblib.load("svc_model.pkl")
except Exception:
    texts_a = [
        "this is a plagiarized sentence example",
        "completely unique text",
    ]
    texts_b = [
        "this is a plagiarized sentence example",
        "another different text",
    ]
    labels = [1, 0]  # 1 = plagiarized, 0 = not

    pairs = [a + " [SEP] " + b for a, b in zip(texts_a, texts_b)]
    vectorizer = TfidfVectorizer()
    X = vectorizer.fit_transform(pairs)

    model = SVC(probability=True)
    model.fit(X, labels)

    joblib.dump(vectorizer, "vectorizer.pkl")
    joblib.dump(model, "svc_model.pkl")


@app.route("/predict_plagiarism", methods=["POST"])
def predict_plagiarism():
    data = request.get_json()
    text = data.get("text", "")
    existing = data.get("existing", [])  # list of { "text": "..." }

    if not existing:
        return jsonify({
            "max_prob": 0.0,
            "avg_prob": 0.0,
            "scores": [],
        })

    scores = []
    probs = []

    for item in existing:
        other = item.get("text", "")
        pair = text + " [SEP] " + other
        X = vectorizer.transform([pair])
        prob = model.predict_proba(X)[0][1]  # probability of class 1
        scores.append({
            "id": item.get("id"),
            "prob": float(prob),
        })
        probs.append(prob)

    max_prob = max(probs)
    avg_prob = sum(probs) / len(probs)

    return jsonify({
        "max_prob": float(max_prob),
        "avg_prob": float(avg_prob),
        "scores": scores,
    })


if __name__ == "__main__":
    app.run(host="127.0.0.1", port=5001, debug=False)
