import pandas as pd
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.svm import SVC
import joblib

# 1) Load the labeled pairs from CSV
# Make sure plagiarism_pairs.csv is in the same folder as this script.
df = pd.read_csv("plagiarism_pairs.csv")

# 2) Build input texts: "text_a [SEP] text_b"
pairs = df["text_a"].astype(str) + " [SEP] " + df["text_b"].astype(str)
labels = df["label"].astype(int)

# 3) TF-IDF vectorizer
vectorizer = TfidfVectorizer()
X = vectorizer.fit_transform(pairs)

# 4) Train SVC model
model = SVC(kernel="linear", probability=True, random_state=42)
model.fit(X, labels)

# 5) Save vectorizer and model
joblib.dump(vectorizer, "vectorizer.pkl")
joblib.dump(model, "svc_model.pkl")

print("Training done. Saved vectorizer.pkl and svc_model.pkl.")
