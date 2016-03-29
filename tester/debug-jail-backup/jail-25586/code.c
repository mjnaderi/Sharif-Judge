#define main themainmainfunction
#include <iostream>
#include <vector>
#include <utility>
#include <algorithm>
using namespace std;

int eval_solution(const vector<int>& job, const vector<int>& solution, int n, int m){
    vector <int> total_time(m, 0);

    int t_max = job[0];
    int m_max = solution[0];

    for(int i = 0; i < job.size(); i++){
        int a = total_time[solution[i]] += job[i];
        if (a > t_max){
            t_max = a;
            m_max = i;
        }
    }

    return t_max;
}

bool compare_job(pair<int, int> i, pair<int, int> j){
    return i.first > j.first;
}


int main()
{

    int n, m;
    cin >> n >> m;

    vector< pair<int,int> >  job;
    job.reserve(n);

    vector<int>real_job_list;
    for(int i = 0; i < n; i++){
        int x; cin >> x;
        job.push_back(make_pair(x, i));
        real_job_list.push_back((x));
    }

    stable_sort(job.begin(), job.end(), compare_job);

    vector<int> sol(n,0);
    vector<int> total_time(m,0);

    for(int i = 0; i < job.size(); i++){
        //Tim may ranh nhat
        int mi_idx = 0;
        for(int j = 0; j < total_time.size(); j++){
            if (total_time[j] < total_time[mi_idx]) mi_idx = j;
        }
        //Phan cong cho no
        total_time[mi_idx] += job[i].first;
        sol[job[i].second] = mi_idx;
    }

    for(int i = 0; i < job.size(); i++){
        cout << sol[i] << " ";
    }
    cout << endl << eval_solution(real_job_list, sol,  n, m);

    return 0;
}
