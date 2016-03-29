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
    return i.first < j.first;
}

void list_solution(vector<int> &solution, int n, int m, int &_min, const vector<int> &job, vector<int>& min_solution){
    if (n == 0){
        int a =  eval_solution(job, solution, n, m);
        if (_min == -1 || a < _min) {
            _min = a;
            min_solution = vector<int>( solution );

        }
        return ;
    }
    for(int i = 0; i < m; i++){
        solution[n-1] = i;
        list_solution(solution, n - 1, m, _min, job, min_solution);
    }
}

int main()
{
    int n, m;
    cin >> n >> m;


    vector< int >  job(n, 0);


    for(int i = 0; i < n; i++){
        int x; cin >> job[i];
        //job.push_back(x);
    }


    int _min = -1;
    vector<int> sol(n, 0);
    vector<int> min_sol;

    list_solution(sol, n, m, _min, job, min_sol );

    for(int i = 0; i < min_sol.size(); i++){
        cout << min_sol[i] << " ";

    }
    //cout << " ==> " << eval_solution(job, min_sol, n, m);



    return 0;
}
