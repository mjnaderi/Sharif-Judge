#define main themainmainfunction
#include <iostream>
#include <algorithm>
#include <utility>
#include <vector>
using namespace std;


bool compare_job(pair<int, int> i, pair<int, int> j){
    return i.first < j.first;
}

int main()
{
    int n, m;
    cin >> n;

    vector< pair<int,int> >  job;
    job.reserve(n);


    for(int i = 0; i < n; i++){
        int x; cin >> x;
        job.push_back(make_pair(x, i));
    }

    stable_sort(job.begin(), job.end(), compare_job);

    for(int i = 0; i < n; i++){
        cout << job[i].second << " ";
    }
    cout << endl;
    return 0;
}
