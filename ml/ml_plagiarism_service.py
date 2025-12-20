from flask import Flask, request, jsonify
import joblib

# 1) Load saved vectorizer and model
vectorizer = joblib.load("vectorizer.pkl")
model = joblib.load("svc_model.pkl")

app = Flask(__name__)

@app.route("/predict_plagiarism", methods=["POST"])
def predict_plagiarism():
    data = request.get_json()
    text = data.get("text", "")
    existing = data.get("existing", [])  # list of { "id": ..., "text": "..." }

    if not text or not existing:
        return jsonify({
            "max_prob": 0.0,
            "avg_prob": 0.0,
            "scores": [],
        })

    pairs = []
    ids = []
    for item in existing:
        other = item.get("text", "")
        if not other:
            continue
        # same pattern as training: text_a [SEP] text_b
        pairs.append(text + " [SEP] " + other)
        ids.append(item.get("id"))

    if not pairs:
        return jsonify({
            "max_prob": 0.0,
            "avg_prob": 0.0,
            "scores": [],
        })

    X = vectorizer.transform(pairs)
    probs = model.predict_proba(X)[:, 1]  # probability of plagiarized class

    scores = []
    for i, prob in zip(ids, probs):
        scores.append({
            "id": i,
            "prob": float(prob),
        })

    max_prob = float(probs.max())
    avg_prob = float(probs.mean())

    return jsonify({
        "max_prob": max_prob,
        "avg_prob": avg_prob,
        "scores": scores,
    })

if __name__ == "__main__":
    app.run(host="127.0.0.1", port=5001, debug=False)
