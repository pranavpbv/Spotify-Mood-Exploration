from flask import Flask, jsonify, request
from flask_cors import CORS
from pymongo import MongoClient
import pandas as pd
from sklearn.preprocessing import MinMaxScaler
from sklearn.metrics.pairwise import euclidean_distances

app = Flask(__name__)
CORS(app) 

client = MongoClient("mongodb+srv://spotifymoodexploration_db_user:music123pc@spotify-mood-exploratio.suiotpe.mongodb.net/?appName=Spotify-Mood-Exploration")
db = client["Final_Project"]
collection = db["Music"]

FEATURES = ['acousticness', 'danceability', 'energy',
            'valence', 'loudness', 'instrumentalness', 'len']

def load_data():
    docs = list(collection.find({}, {'_id': 0, 'track_name': 1,
        'artist_name': 1, 'genre': 1,
        **{f: 1 for f in FEATURES}}))
    return pd.DataFrame(docs)

@app.route('/api/similar', methods=['GET'])
def get_similar():
    selected = request.args.get('track', '')
    if not selected:
        return jsonify({'error': 'No track provided'}), 400

    df = load_data()
    df = df.drop_duplicates(subset='track_name').reset_index(drop=True)

    if selected not in df['track_name'].values:
        return jsonify({'error': 'Track not found'}), 404

    # Normalize features
    scaler = MinMaxScaler()
    scaled = pd.DataFrame(
        scaler.fit_transform(df[FEATURES]),
        columns=FEATURES
    )

    # Compute distances
    selected_idx = df[df['track_name'] == selected].index[0]
    selected_vec = scaled.iloc[[selected_idx]][FEATURES].values
    all_vecs = scaled[FEATURES].values
    distances = euclidean_distances(selected_vec, all_vecs)[0]

    df['distance'] = distances
    max_dist = df[df['track_name'] != selected]['distance'].max()
    df['similarity'] = ((1 - df['distance'] / max_dist) * 100).round(1)

    # Top 10 excluding selected song
    top10 = (df[df['track_name'] != selected]
             .sort_values('distance')
             .head(10)[['track_name', 'artist_name', 'genre', 'similarity']]
             .to_dict(orient='records'))

    return jsonify({'selected': selected, 'results': top10})

@app.route('/api/tracks', methods=['GET'])
def get_tracks():
    # Returns all track names for the search dropdown
    tracks = collection.distinct('track_name')
    return jsonify(sorted(tracks))

if __name__ == '__main__':
    app.run(debug=True, port=5000)